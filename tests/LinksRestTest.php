<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Links;
use WPFastyBuilder\Rest;

/**
 * GET wpfasty-builder/v1/links (Links::index): the editor's link picker data — site tokens, published top-level
 * pages, categories and post type archives with the URL each token resolves to; one fixed set of queries.
 */
#[RunTestsInSeparateProcesses]
final class LinksRestTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/fixtures/wp-objects-stubs.php';
        require_once __DIR__ . '/fixtures/links-stubs.php';
        require_once __DIR__ . '/fixtures/links-index-stubs.php';
        require_once __DIR__ . '/fixtures/rest-stubs.php';
        require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Links.php';
        require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Rest.php';
        $GLOBALS['wpfb_links'] = [
            'pages' => [
                new \WP_Post(['ID' => 11, 'post_name' => 'about', 'post_title' => 'About <b>us</b>', 'post_type' => 'page']),
                new \WP_Post(['ID' => 12, 'post_name' => 'contact', 'post_title' => '', 'post_type' => 'page']),
                // not addressable by {page:<slug>}: skipped
                new \WP_Post(['ID' => 13, 'post_name' => '%d0%be-%d0%bd%d0%b0%d1%81', 'post_title' => 'О нас', 'post_type' => 'page']),
            ],
            'terms' => [new \WP_Term(['term_id' => 5, 'name' => 'News', 'slug' => 'news'])],
            'types' => ['product' => 'Products'],
            'posts_page' => 0,
            'calls' => [],
            'args' => [],
        ];
    }

    public function testRouteNeedsEditPages(): void
    {
        $GLOBALS['wp_test_routes'] = [];
        Rest::routes();
        $route = $GLOBALS['wp_test_routes'][Rest::NS . '/links'];
        $this->assertSame('GET', $route['methods']);
        $GLOBALS['wp_test_caps'] = [];
        $this->assertFalse(($route['permission_callback'])());
        $GLOBALS['wp_test_caps'] = ['edit_pages'];
        $this->assertTrue(($route['permission_callback'])());
    }

    public function testIndexListsEveryTargetWithItsUrl(): void
    {
        $data = Rest::links()->get_data();
        $this->assertSame('http://example.test/', $data['home']);
        $this->assertSame([
            ['token' => '{home}', 'label' => 'Home', 'url' => 'http://example.test/'],
            ['token' => '{posts}', 'label' => 'Posts', 'url' => 'http://example.test/'],
        ], $data['site']);
        $this->assertSame([
            ['token' => '{page:about}', 'label' => 'About us', 'slug' => 'about', 'url' => 'http://example.test/about/'],
            ['token' => '{page:contact}', 'label' => 'contact', 'slug' => 'contact', 'url' => 'http://example.test/contact/'],
        ], $data['pages']);
        $this->assertSame([
            ['token' => '{category:news}', 'label' => 'News', 'slug' => 'news', 'kind' => 'category', 'url' => 'http://example.test/category/news/'],
            ['token' => '{archive:product}', 'label' => 'Products', 'slug' => 'product', 'kind' => 'archive', 'url' => 'http://example.test/product/'],
        ], $data['sections']);
        // the listed tokens resolve server-side to the URL the editor shows (Links::resolve; the category lookup of
        // links-stubs.php finds nothing, so only site and archive tokens are compared here)
        foreach ([...$data['site'], $data['sections'][1]] as $t) {
            $this->assertSame($t['url'], Links::resolve($t['token']), $t['token']);
        }
    }

    public function testOneQueryPerList(): void
    {
        $data = Links::index();
        $calls = $GLOBALS['wpfb_links']['calls'];
        // one call per list, whatever the number of items (no per-item queries: links come from the loaded objects)
        $this->assertSame(['get_posts' => 1, 'get_terms' => 1, 'get_post_types' => 1], $calls);
        $args = $GLOBALS['wpfb_links']['args']['get_posts'];
        $this->assertSame(['page', 'publish', 0, true, false, false], [
            $args['post_type'], $args['post_status'], $args['post_parent'], $args['no_found_rows'],
            $args['update_post_meta_cache'], $args['update_post_term_cache'],
        ]);
        $this->assertSame([['public' => true, 'has_archive' => true], 'objects'], $GLOBALS['wpfb_links']['args']['get_post_types']);
        $this->assertSame(Links::MAX, $args['numberposts']);
        $this->assertIsArray($data['pages']);
    }

    public function testContactLinksFromSiteData(): void
    {
        $d = [
            'phone' => '+7 (900) 123-45-67', 'whatsapp' => '+7 (900) 123-45-67', 'email' => 'hello@example.com',
            'address' => 'Tverskaya 1, Moscow', 'telegram' => '@demo_shop',
        ];
        $this->assertSame('tel:+79001234567', Links::contact('tel', $d));
        $this->assertSame('tel:89001234567', Links::contact('tel', ['phone' => '8 900 123 45 67']));
        $this->assertSame('https://wa.me/79001234567', Links::contact('whatsapp', $d));
        $this->assertSame('mailto:hello@example.com', Links::contact('mailto', $d));
        $this->assertSame('https://t.me/demo_shop', Links::contact('telegram', $d));
        $this->assertSame(Links::MAP . 'Tverskaya%201%2C%20Moscow', Links::contact('map', $d));
        // no value (or one that cannot make a link): "#"
        foreach (array_keys(Links::CONTACT) as $kind) {
            $this->assertSame('#', Links::contact($kind, []), $kind);
        }
        $this->assertSame('#', Links::contact('mailto', ['email' => 'not an email']));
        $this->assertSame('#', Links::contact('telegram', ['telegram' => 'a b']));
    }

    public function testSiteDataAndDateTokensInText(): void
    {
        // the site has no phone, address… in its settings yet: "" for site data, "#" for contact links
        $this->assertSame('© 2026 ', Links::resolve('© {year} {site:name}'));
        $this->assertSame('Today: October 28, 2026', Links::resolve('Today: {today}'));
        $this->assertSame('Call  at #', Links::resolve('Call {site:phone} at {tel}'));
        $this->assertSame('', Links::resolve('{site:unknown}'));
        $this->assertSame('#?x=1', Links::resolve('{map}?x=1'));
        $this->assertSame([
            'name' => '', 'phone' => '', 'email' => '', 'address' => '', 'hours' => '', 'telegram' => '',
            'whatsapp' => '', 'lat' => '', 'lng' => '', 'provider' => '',
        ], Links::data());
        // {open-now} without opening hours: nothing
        $this->assertSame('a  b', Links::resolve('a {open-now} b'));
    }

    public function testIndexListsContactAndDataTokens(): void
    {
        $data = Links::index();
        $this->assertSame(
            ['{tel}' => '#', '{mailto}' => '#', '{whatsapp}' => '#', '{telegram}' => '#', '{map}' => '#'],
            array_column($data['contact'], 'url', 'token'),
        );
        $this->assertSame(['phone', 'email', 'whatsapp', 'telegram', 'address'], array_column($data['contact'], 'source'));
        $this->assertSame(
            ['{site:name}' => '', '{site:phone}' => '', '{site:email}' => '', '{site:address}' => '', '{site:hours}' => '', '{year}' => '2026', '{today}' => 'October 28, 2026'],
            array_column($data['data'], 'value', 'token'),
        );
        foreach ([...$data['contact'], ...$data['data']] as $t) {
            $this->assertSame($t['url'] ?? $t['value'], Links::resolve($t['token']), $t['token']);
        }
    }

    /** @return array<string, mixed> the settings contact block of the demo shop (SettingsV04 contact, hours, map) */
    private static function block(): array
    {
        return [
            'contact' => [
                'phone' => '+12025550143', 'email' => 'hello@example.com', 'address' => '1 Example Street, Springfield',
                'links' => [
                    ['network' => 'instagram', 'handle' => 'example.shop'],
                    ['network' => 'telegram', 'handle' => 'example_shop'],
                    ['network' => 'whatsapp', 'handle' => '+12025550188'],
                ],
            ],
            'hours' => [
                ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'open' => '09:00', 'close' => '18:00'],
                ['days' => ['sat'], 'open' => '10:00', 'close' => '14:00'],
            ],
            'hours_exceptions' => [['date' => '2026-12-25', 'closed' => true]],
            'map' => ['provider' => 'google', 'lat' => 38.8977, 'lng' => -77.0365],
        ];
    }

    public function testSiteDataAndContactLinksComeFromTheSettingsContactBlock(): void
    {
        Links::setBlock(self::block());
        $d = Links::data();
        $this->assertSame('+12025550143', $d['phone']);
        $this->assertSame('example_shop', $d['telegram']);
        $this->assertSame('+12025550188', $d['whatsapp']); // the WhatsApp link, not the phone
        $this->assertSame('Mon–Fri 09:00–18:00; Sat 10:00–14:00', $d['hours']);
        $this->assertSame(
            'Call +12025550143 · hello@example.com · 1 Example Street, Springfield',
            Links::resolve('Call {site:phone} · {site:email} · {site:address}'),
        );
        $this->assertSame('tel:+12025550143', Links::resolve('{tel}'));
        $this->assertSame('mailto:hello@example.com?subject=Hi', Links::resolve('{mailto}?subject=Hi'));
        $this->assertSame('https://wa.me/12025550188', Links::resolve('{whatsapp}'));
        $this->assertSame('https://t.me/example_shop', Links::resolve('{telegram}'));
        // a pin at lat/lng, with the provider of the settings map
        $this->assertSame('https://www.google.com/maps/search/?api=1&query=38.8977%2C-77.0365', Links::resolve('{map}'));
        // WhatsApp falls back to the phone when contact.links has no whatsapp
        $b = self::block();
        $b['contact']['links'] = [];
        Links::setBlock($b);
        $this->assertSame('https://wa.me/12025550143', Links::resolve('{whatsapp}'));
        $this->assertSame('#', Links::resolve('{telegram}'));
        $index = Links::index();
        $this->assertSame('Mon–Fri 09:00–18:00; Sat 10:00–14:00', array_column($index['data'], 'value', 'token')['{site:hours}']);
        foreach ([...$index['contact'], ...$index['data']] as $t) {
            $this->assertSame($t['url'] ?? $t['value'], Links::resolve($t['token']), $t['token']);
        }
    }

    public function testMapLinkByProviderPinOrAddress(): void
    {
        $address = ['address' => '1 Example Street, Springfield'];
        $pin = ['lat' => '51.5007', 'lng' => '-0.1246'];
        $this->assertSame(Links::MAP . '1%20Example%20Street%2C%20Springfield', Links::contact('map', $address));
        $this->assertSame(
            'https://www.openstreetmap.org/?mlat=51.5007&mlon=-0.1246#map=17/51.5007/-0.1246',
            Links::contact('map', $address + $pin),
        );
        $this->assertSame('https://yandex.com/maps/?pt=-0.1246,51.5007&z=17&l=map', Links::contact('map', $pin + ['provider' => 'yandex']));
        $this->assertSame('https://maps.apple.com/?q=1%20Example%20Street%2C%20Springfield', Links::contact('map', $address + ['provider' => 'apple']));
        $this->assertSame(Links::MAP . 'x', Links::contact('map', ['address' => 'x', 'provider' => 'unknown']));
        $this->assertSame('#', Links::contact('map', ['lat' => '51.5', 'provider' => 'osm']));
    }

    public function testHoursTextGroupsDaysAndIntervals(): void
    {
        $this->assertSame('', Links::hoursText([]));
        $this->assertSame('Mon–Sun 00:00–24:00', Links::hoursText([['days' => Links::WEEK, 'open' => '00:00', 'close' => '24:00']]));
        // lunch break, days given out of order, a night shift, a closed Wednesday
        $this->assertSame(
            'Mon–Tue 09:00–13:00, 14:00–18:00; Thu 09:00–13:00, 14:00–18:00; Fri–Sat 20:00–02:00',
            Links::hoursText([
                ['days' => ['thu', 'mon', 'tue'], 'open' => '14:00', 'close' => '18:00'],
                ['days' => ['mon', 'tue', 'thu'], 'open' => '09:00', 'close' => '13:00'],
                ['days' => ['fri', 'sat'], 'open' => '20:00', 'close' => '02:00'],
            ]),
        );
    }

    public function testOpenNowIsAClientElementOnlyWithHours(): void
    {
        Links::setBlock(self::block());
        $this->assertSame('Now: ' . Links::OPEN_NOW, Links::resolve('Now: {open-now}'));
        $html = '<p class="x" title="' . Links::OPEN_NOW . '">Now: ' . Links::OPEN_NOW . '</p>';
        $this->assertSame('<p class="x" title="">Now: <span data-wpfb-open-now></span></p>', Links::finish($html));
        $this->assertSame('<p>plain</p>', Links::finish('<p>plain</p>'));
        $data = Links::openNow();
        $this->assertNotNull($data);
        $this->assertSame(self::block()['hours'], $data['hours']);
        $this->assertSame(self::block()['hours_exceptions'], $data['exceptions']);
        $this->assertSame(['Open now', 'Closed now'], [$data['open'], $data['closed']]);
        Links::setBlock(['contact' => ['phone' => '+12025550143']]);
        $this->assertNull(Links::openNow());
        $this->assertSame('', Links::resolve('{open-now}'));
    }

    /** The inline script against fixed clocks (node or bun on PATH; skipped without one). */
    public function testOpenNowScriptDecidesFromHoursExceptionsAndZone(): void
    {
        $bin = trim((string) shell_exec('command -v node || command -v bun'));
        if ($bin === '') {
            $this->markTestSkipped('no node / bun');
        }
        $data = [
            'tz' => 'America/New_York', 'offset' => -300, 'open' => 'Open now', 'closed' => 'Closed now',
            'hours' => [
                ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'open' => '09:00', 'close' => '18:00'],
                ['days' => ['sat'], 'open' => '22:00', 'close' => '02:00'],
            ],
            'exceptions' => [['date' => '2026-12-25', 'closed' => true], ['date' => '2026-12-24', 'open' => '09:00', 'close' => '13:00']],
        ];
        $cases = [
            '2026-10-28T13:30:00Z' => 'open',    // Wed 09:30 New York (EDT, UTC-4)
            '2026-10-28T12:30:00Z' => 'closed',  // Wed 08:30
            '2026-10-28T22:00:00Z' => 'closed',  // Wed 18:00 (close is exclusive)
            '2026-11-01T03:30:00Z' => 'open',    // Sat 23:30 EDT (night shift)
            '2026-11-01T06:30:00Z' => 'open',    // Sun 01:30 EST, still Saturday's shift (DST switch)
            '2026-11-01T07:30:00Z' => 'closed',  // Sun 02:30 EST
            '2026-12-25T15:00:00Z' => 'closed',  // Fri 10:00 EST, Christmas exception
            '2026-12-24T17:00:00Z' => 'open',    // Thu 12:00, short day
            '2026-12-24T19:00:00Z' => 'closed',  // Thu 14:00, short day over
        ];
        $js = 'var R={};var els=[];var document={querySelectorAll:function(){return els},querySelector:function(){return null},body:{}};'
            . 'var window={};var setInterval=function(){};var RD=Date;'
            . 'var C=' . json_encode(array_keys($cases)) . ';'
            . 'C.forEach(function(iso){var T=new RD(iso).getTime();Date=function(a){return a===undefined?new RD(T):new RD(a)};'
            . 'Date.UTC=RD.UTC;var el={textContent:"",setAttribute:function(k,v){this.s=v}};els=[el];'
            . '(function(d){' . Links::OPEN_NOW_JS . '})(' . json_encode($data) . ');R[iso]=el.s+"|"+el.textContent});'
            . 'console.log(JSON.stringify(R));';
        $file = tempnam(sys_get_temp_dir(), 'opennow') . '.js';
        file_put_contents($file, $js);
        $out = json_decode((string) shell_exec(escapeshellarg($bin) . ' ' . escapeshellarg($file)), true);
        unlink($file);
        foreach ($cases as $iso => $state) {
            $this->assertSame($state . '|' . ($state === 'open' ? 'Open now' : 'Closed now'), $out[$iso] ?? null, $iso);
        }
        // a zone the browser does not know: the fixed offset (here UTC-5) is used
        $data['tz'] = 'Not/AZone';
        $js2 = str_replace(json_encode(array_merge($data, ['tz' => 'America/New_York'])), json_encode($data), $js);
        $file = tempnam(sys_get_temp_dir(), 'opennow') . '.js';
        file_put_contents($file, $js2);
        $out = json_decode((string) shell_exec(escapeshellarg($bin) . ' ' . escapeshellarg($file)), true);
        unlink($file);
        $this->assertSame('closed|Closed now', $out['2026-10-28T13:30:00Z'] ?? null); // 08:30 at UTC-5
    }
}
