<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use Latte\Engine;
use Latte\Loaders\StringLoader;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\BlockRenderer;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Codex\ReadPath;
use WPFastyBuilder\Compiler;
use WPFastyBuilder\DataSources;
use WPFastyBuilder\Fragment;
use WPFastyBuilder\Front;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Links;
use WPFastyBuilder\Safe;
use WPFastyBuilder\Source\LocalSource;


/**
 * XSS / template injection of the builder (docs: /workspace/ui8kit3/audit/security-xss.md): URL props through
 * obfuscation, template code in the page title, prop types and markup of a saved layout, TrustedHtml of meta fields,
 * `|html` of a non-string, unpublished pages behind {page:}, and a fuzz sample (tools/security/xss-fuzz.php runs every
 * variant with every payload in CI).
 */
#[RunTestsInSeparateProcesses]
final class SecurityXssTest extends TestCase
{
    private const SRC = __DIR__ . '/../plugins/wpfasty-builder/sources/ui8kit';

    /** @return array{0: Catalog, 1: BlockRenderer} */
    private static function runtime(string $engine): array
    {
        $src = new LocalSource('ui8kit', self::SRC . '/' . $engine, $engine);
        return [new Catalog($src->catalog()), new BlockRenderer(['ui8kit' => $src], null)];
    }

    /** WP stubs per process: get_post_type & co. must not exist for ShopCartTest, {page:} lookups are cached per request */
    protected function setUp(): void
    {
        require_once __DIR__ . '/fixtures/links-stubs.php';
        require_once __DIR__ . '/fixtures/rest-stubs.php';
        require_once __DIR__ . '/fixtures/wp-objects-stubs.php';
        spl_autoload_register(static function (string $class): void {
            if (str_starts_with($class, 'WPFastyBuilder\\')) {
                $file = dirname(__DIR__) . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        });
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp_test_kses'], $GLOBALS['wp_test_pages']);
    }

    public function testDangerousUrlSchemesAreDroppedThroughObfuscation(): void
    {
        $bad = [
            'javascript:alert(1)', 'JaVaScRiPt:alert(1)', ' javascript:alert(1)', "java\tscript:alert(1)",
            "java\nscript:alert(1)", "java\rscript:alert(1)", "\x01javascript:alert(1)", "javascript\x00:alert(1)",
            'jav&#x09;ascript:alert(1)', 'jav&#9;ascript:alert(1)', '&#106;avascript:alert(1)',
            '&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;:alert(1)', 'javascript&colon;alert(1)',
            '&amp;#106;avascript:alert(1)', 'vbscript:msgbox(1)', 'livescript:alert(1)', 'mocha:alert(1)',
            'data:text/html,<script>alert(1)</script>', 'DATA:text/html;base64,PHNjcmlwdD4=',
            'data:image/svg+xml;base64,PHN2Zz4=" onerror="x', 'blob:https://evil.test/x', 'filesystem:x',
        ];
        foreach ($bad as $u) {
            self::assertSame('', Safe::url($u), "href: $u");
            if (!str_starts_with($u, 'data:image')) {
                self::assertSame('', Safe::url($u, true), "src: $u");
            }
        }
        foreach (['/a', '#x', '?q=1', 'page.html', 'https://example.test/a?b=c#d', 'mailto:a@b.test', 'tel:+1'] as $u) {
            self::assertSame($u, Safe::url($u), $u);
        }
        // data: only an image, only in an image key
        self::assertSame('data:image/png;base64,iVBORw0=', Safe::url('data:image/png;base64,iVBORw0=', true));
        self::assertSame('', Safe::url('data:image/png;base64,iVBORw0='));
        // srcset: every candidate
        self::assertSame('/a.jpg 1x, /b.jpg 2x', Safe::url('/a.jpg 1x, /b.jpg 2x', true));
        self::assertSame('', Safe::url('/a.jpg 1x, javascript:alert(1) 2x', true));
        // props: URL keys anywhere (lists inherit their key), C0 controls out of text, markers untouched
        $props = Safe::props([
            'href' => 'javascript:alert(1)', 'title' => "a\x01b", 'cta' => ['href' => 'vbscript:x', 'label' => 'L'],
            'images' => ['javascript:1', '/ok.png'], 'avatarUrl' => 'data:text/html,x', 'body' => ['__html' => '<b>b</b>'],
        ]);
        self::assertSame([
            'href' => '', 'title' => 'ab', 'cta' => ['href' => '', 'label' => 'L'], 'images' => ['', '/ok.png'],
            'avatarUrl' => '', 'body' => ['__html' => '<b>b</b>'],
        ], $props);
        self::assertTrue(Safe::isUrlKey('posterSrc'));
        self::assertFalse(Safe::isUrlKey('label'));
    }

    /**
     * Protocol-relative URLs are refused (not rewritten to https://): //host, /\host, \\host, \/host — also behind
     * entities, spaces, tabs, newlines; a site path, #, ? and http(s) stay. At save (Layout::safeHref → "#/") and at
     * render (Safe::url → "").
     */
    public function testProtocolRelativeUrlsAreRefused(): void
    {
        $bad = ['//evil.test/x', '/\\evil.test', '\\\\evil.test', '\\/evil.test', ' //evil.test', "\t//evil.test",
            "/\t/evil.test", "/\n/evil.test", '&#47;/evil.test', '&#x2F;&#x2F;evil.test', '&sol;&sol;evil.test',
            "\x01//evil.test"];
        foreach ($bad as $u) {
            self::assertTrue(Safe::networkPath($u), json_encode($u));
            self::assertSame('', Safe::url($u), json_encode($u));
            self::assertSame('', Safe::url($u, true), json_encode($u));
            self::assertFalse(Layout::safeHref($u), json_encode($u));
        }
        foreach (['/', '/about/', '/a//b', '#team', '?s=x', 'https://example.test/', 'mailto:a@b.test', 'tel:+1', '{page:about}'] as $u) {
            self::assertTrue(Layout::safeHref($u), $u);
            self::assertFalse(Safe::networkPath($u), $u);
        }
        // bare words stay for URL-named enum props of the catalog (cover: "wide", actions: "pair"); they cannot leave
        // the site, href refuses them at save
        self::assertSame('wide', Safe::url('wide'));
        self::assertFalse(Layout::safeHref('wide'));
        self::assertSame(['href' => '', 'logos' => ['', '/l.png']], Safe::props(['href' => '//evil.test', 'logos' => ['\\\\evil/l.png', '/l.png']]));
    }

    /** a list under a URL key (singular or plural) is checked item by item */
    public function testListItemsInheritTheirKey(): void
    {
        self::assertSame(['srcset' => ['', '/a.png']], Safe::props(['srcset' => ['javascript:1', '/a.png']]));
        self::assertSame(['hrefs' => ['', '/a'], 'logos' => [['src' => '']]], Safe::props(['hrefs' => ['vbscript:1', '/a'], 'logos' => [['src' => 'javascript:1']]]));
    }

    /**
     * The page title (and every literal) is written into the compiled template: template code in it never runs
     * (it did: `**}} {=strtoupper("rce")} {**` closed the Latte comment), control characters do not break the page.
     */
    public function testPageTitleCannotInjectTemplateCode(): void
    {
        $titles = [
            '**}} {=strtoupper("rce")} {**', '##}} {{ "rce"|upper }} {##', '*} {=1300+37} {*', '#} {{ 1300+37 }} {#',
            '{% set a = 1300 + 37 %}{{ a }}', "Title \x01\x02\x1B ok", '-->{=1300+37}<!--',
        ];
        foreach ($titles as $title) {
            foreach (['latte', 'twig'] as $engine) {
                [$catalog, $r] = self::runtime($engine);
                [$entry] = Layout::normalize(['blocks' => [
                    ['id' => 'h', 'block' => 'pagehead', 'variant' => 'start', 'props' => ['title' => $title, 'text' => $title]],
                ]], 'ui8kit', $catalog, [], ['id' => 'l', 'slug' => 'l', 'title' => $title]);
                $tpl = Compiler::compile($entry, $catalog, $engine);
                $fn = static fn (string $s, string $t, array $p): string => $r->render($s, $t, Front::prepare($p));
                if ($engine === 'latte') {
                    $l = new Engine();
                    $l->setTempDirectory(null);
                    $l->setLoader(new StringLoader(['page' => $tpl]));
                    $l->addFunction('wpfb_block', $fn);
                    $html = $l->renderToString('page', ['wpfb' => []]);
                } else {
                    $env = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['page' => $tpl]), ['autoescape' => 'html']);
                    $env->registerUndefinedFunctionCallback(static fn (string $n): \Twig\TwigFunction|false
                        => $n === 'wpfb_block' ? new \Twig\TwigFunction($n, $fn, ['is_safe' => ['html']]) : false);
                    $html = $env->render('page', ['wpfb' => []]);
                }
                self::assertStringNotContainsString('RCE', $html, "$engine: $title");
                self::assertStringNotContainsString('1337', $html, "$engine: $title");
                self::assertStringNotContainsString("\x01", $html, "$engine: $title");
            }
        }
        self::assertStringNotContainsString('}', Compiler::comment('**}} {=x} {**'));
        self::assertStringNotContainsString('{', Compiler::comment('**}} {=x} {**'));
    }

    public function testSavedLayoutPropsAreTextOfTheSampleType(): void
    {
        [$catalog] = self::runtime('latte');
        $deep = ['x' => 'y'];
        for ($i = 0; $i < 40; $i++) {
            $deep = ['n' => $deep];
        }
        [$entry, $issues] = Layout::normalize(['blocks' => [[
            'id' => 'h', 'block' => 'pagehead', 'variant' => 'start', 'props' => [
                'title' => ['__html' => '<script>alert(1)</script>'],
                'text' => '<img src=x onerror=alert(1)>Text',
                'cta' => "Go\x00\x01",
                'href' => 'jav&#x09;ascript:alert(1)',
                'level' => '<b>2</b>',
                'imageSrc' => 'javascript:alert(1)',
                'extra' => ['n' => ['__html' => '<i>x</i>', 'k' => '<svg onload=alert(1)>v']],
                'deep' => $deep,
            ],
        ]]], 'ui8kit', $catalog, [], ['id' => 'l', 'slug' => 'l', 'title' => 'L']);
        $props = (array) Layout::blocks($entry)[0]['props'];
        $codes = array_column($issues, 'code');
        self::assertArrayNotHasKey('title', $props, 'a marker or an object where the sample has text is dropped');
        self::assertSame('Text', $props['text']);
        self::assertSame('Go', $props['cta']);
        self::assertSame('#/', $props['href']);
        self::assertArrayNotHasKey('level', $props, 'a string where the sample has a number is dropped');
        self::assertSame('', $props['imageSrc']);
        self::assertSame(['n' => ['k' => 'v']], $props['extra']);
        self::assertContains('layout.prop_type', $codes);
        self::assertContains('layout.props_too_deep', $codes);
        $json = (string) json_encode($props);
        self::assertStringNotContainsString('__html', $json);
        self::assertStringNotContainsString('<', $json);
    }

    public function testTrustedHtmlOfMetaIsKsesFiltered(): void
    {
        $GLOBALS['wp_test_kses'] = static fn (string $h): string => (string) preg_replace('#<script\b.*?</script>|\son\w+="[^"]*"#is', '', $h);
        $aside = ['__html' => '<p onclick="x()">Note</p><script>alert(1)</script>'];
        self::assertSame(['__html' => '<p>Note</p>'], DataSources::trusted($aside, false), 'meta / settings: wp_kses_post');
        self::assertSame($aside, DataSources::trusted($aside, true), 'the_content of the current post: as filtered by WP');
        self::assertNull(DataSources::trusted('<script>', false));
        self::assertNull(DataSources::trusted(['__html' => ['x']], false));
        self::assertNull(DataSources::trusted(null, true));
    }

    public function testHtmlPipeOfANonStringIsNull(): void
    {
        // a blank string is absent too (codegen-x readPath since 89a08eff: PHP trim(), so a no-break space is text)
        foreach ([['a' => '<b>'], 7, 1.5, true, null, '', ' ', "\t\n", "\0 "] as $v) {
            self::assertNull(ReadPath::read(['f' => $v], 'f|html'), var_export($v, true));
        }
        self::assertSame(['__html' => '<b>x</b>'], ReadPath::read(['f' => '<b>x</b>'], 'f|html'));
        self::assertSame(['__html' => "\u{00a0}"], ReadPath::read(['f' => "\u{00a0}"], 'f|html'));
    }

    public function testPageTokensLinkOnlyPublishedPages(): void
    {
        $GLOBALS['wp_test_pages'] = [
            'about' => new \WP_Post(['ID' => 31, 'post_type' => 'page', 'post_name' => 'about']),
            'secret' => new \WP_Post(['ID' => 32, 'post_type' => 'page', 'post_name' => 'secret', 'post_status' => 'draft']),
            'private' => new \WP_Post(['ID' => 33, 'post_type' => 'page', 'post_name' => 'private', 'post_status' => 'private']),
            'pending' => new \WP_Post(['ID' => 34, 'post_type' => 'page', 'post_name' => 'pending', 'post_status' => 'pending']),
        ];
        $GLOBALS['wp_test_permalinks'][31] = 'http://example.test/about/';
        $GLOBALS['wp_test_permalinks'][32] = 'http://example.test/?page_id=32';
        $GLOBALS['wp_test_permalinks'][33] = 'http://example.test/private/';
        $GLOBALS['wp_test_permalinks'][34] = 'http://example.test/?page_id=34';
        self::assertSame('http://example.test/about/#a', Links::resolve('{page:about}#a'));
        foreach (['secret', 'private', 'pending'] as $slug) {
            self::assertSame('http://example.test/', Links::resolve("{page:$slug}"), $slug);
        }
    }

    /** a sample of tools/security/xss-fuzz.php: the payloads through every string prop of a few blocks, DOM-checked */
    public function testFuzzSampleLatteEqualsTwigAndNothingExecutes(): void
    {
        $payloads = require dirname(__DIR__) . '/tools/security/payloads.php';
        $check = require dirname(__DIR__) . '/tools/security/dom-check.php';
        $dom = require dirname(__DIR__) . '/tools/parity/normalize-dom.php';
        $set = array_merge(array_slice($payloads['html'], 0, 12), array_slice($payloads['url'], 0, 12));
        $put = static function (mixed $v, string $p) use (&$put): mixed {
            if (is_array($v)) {
                return array_key_exists('__html', $v) ? $v : array_map(static fn ($x) => $put($x, $p), $v);
            }
            return is_string($v) ? $p : $v;
        };
        foreach ([['pagehead', 'start'], ['cta', 'card'], ['about', 'split'], ['richtext', 'sidebar']] as [$block, $variant]) {
            $base = [];
            foreach (['latte', 'twig'] as $engine) {
                [$catalog, $r] = self::runtime($engine);
                $props = (array) ($catalog->variant($block, $variant)['props'] ?? []);
                $base[$engine] = Fragment::render($catalog, 'ui8kit', ['id' => 'b', 'block' => $block, 'variant' => $variant, 'props' => $props], [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, Front::prepare($p)), static fn (array $s): array => (array) ($s['sample'] ?? []))['html'];
            }
            foreach ($set as $payload) {
                $out = [];
                foreach (['latte', 'twig'] as $engine) {
                    [$catalog, $r] = self::runtime($engine);
                    $props = $put((array) ($catalog->variant($block, $variant)['props'] ?? []), $payload);
                    $rec = static fn (array $s): array => $put((array) ($s['sample'] ?? []), $payload);
                    $html = Fragment::render($catalog, 'ui8kit', ['id' => 'b', 'block' => $block, 'variant' => $variant, 'props' => $props], [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, Front::prepare($p)), $rec)['html'];
                    self::assertSame([], $check($html, $payload, $base[$engine]), "$engine $block/$variant: $payload");
                    $out[$engine] = $dom($html);
                }
                self::assertSame($out['latte'], $out['twig'], "$block/$variant Latte = Twig: $payload");
            }
        }
    }
}
