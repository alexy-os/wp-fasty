<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use CodegenX\Organisms\Latte\Runtime as LatteRuntime;
use CodegenX\Organisms\Twig\Runtime as TwigRuntime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\BlockRenderer;
use WPFastyBuilder\Codex\ReadPath;
use WPFastyBuilder\DataSources;
use WPFastyBuilder\Safe;
use WPFastyBuilder\Source\LocalSource;

require_once __DIR__ . '/fixtures/links-stubs.php';
require_once __DIR__ . '/fixtures/rest-stubs.php';
require_once dirname(__DIR__) . '/plugins/wpfasty-builder/sources/ui8kit/latte/php/Runtime.php';
require_once dirname(__DIR__) . '/plugins/wpfasty-builder/sources/ui8kit/twig/php/Runtime.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__) . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

/**
 * The output safety codegen-x guarantees in the organism runtime the plugin ships (sources/ui8kit/{latte,twig}/php,
 * codegen-x 832d7f17), held to the codegen-x vectors: tests/fixtures/codex-shared/safety-vectors.json is
 * packages/compose-gen/runtimes/core/test/safety-vectors.json byte for byte (bun tools/codex/readpath-shared.ts --cgx,
 * CI with CODEGEN_X_TOKEN). What these tests prove is what wp-fasty no longer guards twice (raw-sinks: og_slot in
 * any template); the adapter (Safe::props, Layout::clean, DataSources::trusted) stays as the input guard.
 */
final class CodegenSafetySharedTest extends TestCase
{
    private const SRC = __DIR__ . '/../plugins/wpfasty-builder/sources/ui8kit';
    private const XSS = '<img src=x onerror=alert(1)><script>alert(2)</script>';

    /**
     * URL values where Safe (the adapter, wp_allowed_protocols() and an html_entity_decode reading) lets more through
     * than the codegen-x allowlist. None of them runs script: printed escaped (& → &amp;) they stay a relative path
     * or an ftp link, and the runtime refuses them anyway (testAdapterThenRuntimeGivesTheCodegenAnswer). `?q=a:b` is
     * the open question to XGenBot (a relative URL with ':' before any '/').
     */
    private const SAFE_LOOSER = [
        '&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A alert(1)',
        'ftp://example.org/x',
        'url(javascript:alert(1))',
        '&unknown;javascript:x',
        '?q=a:b',
        '&sol;a:b',
    ];

    /** @return array<string, mixed> */
    private static function vectors(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/fixtures/codex-shared/safety-vectors.json'), true);
    }

    /** @return iterable<string, array{class-string}> */
    public static function runtimes(): iterable
    {
        yield 'latte' => [LatteRuntime::class];
        yield 'twig' => [TwigRuntime::class];
    }

    /** @param class-string $rt */
    #[DataProvider('runtimes')]
    public function testShippedRuntimeGivesTheSharedAnswers(string $rt): void
    {
        $v = self::vectors();
        self::assertGreaterThanOrEqual(59, count($v['url']));
        foreach ($v['url'] as [$url, $image, $ok]) {
            self::assertSame($ok, $rt::isSafeUrl($url, $image), json_encode([$url, $image]));
            self::assertSame($ok ? $url : '', $rt::url($url, $image), json_encode([$url, $image]));
        }
        foreach ($v['srcset'] as [$value, $ok]) {
            self::assertSame($ok ? $value : '', $rt::srcset($value), $value);
        }
        foreach ($v['style'] as [$value, $ok]) {
            self::assertSame($ok ? $value : '', $rt::style($value), $value);
        }
        foreach ($v['names'] as [$name, $ok]) {
            self::assertSame($ok, $rt::isSafeAttrName($name), $name);
        }
        foreach ($v['css'] as [$value, $ok]) {
            self::assertSame($ok ? $value : '', $rt::cssValue($value), $value);
        }
        foreach ($v['attrs'] as [$name, $value, $want]) {
            self::assertSame($want, $rt::attrValue($name, $value), "$name=$value");
        }
        // attribute maps: on* names and bad URLs never printed (attr_html / attrs)
        $html = $rt::attrHtml(['onclick' => 'x', 'href' => 'javascript:alert(1)', 'src' => '//evil.example/x', 'title' => 'ok']);
        self::assertSame(' href="" src="" title="ok"', $html);
    }

    public function testAdapterThenRuntimeGivesTheCodegenAnswer(): void
    {
        $looser = [];
        foreach (self::vectors()['url'] as [$url, $image, $ok]) {
            $adapter = Safe::url($url, $image);
            $printed = $adapter === '' ? '' : LatteRuntime::url($adapter, $image);
            // what reaches the page: allowed only when both allow; the adapter never refuses what codegen allows
            self::assertSame($ok ? $url : '', $printed, json_encode([$url, $image]));
            if (!$ok && $adapter === $url) {
                $looser[] = $url;
            }
        }
        self::assertSame(self::SAFE_LOOSER, $looser);
    }

    public function testWordPressLinksTheAdapterKeepsAndCodegenRefuses(): void
    {
        // relative links with ':' before any '/' (a search or filter query, a footnote anchor) and WordPress schemes
        // other than http(s)/mailto/tel pass the adapter but lose their href in codegen-x 832d7f17 (question to XGenBot);
        // the links WordPress itself makes (home_url, get_pagenum_link, link tokens) are absolute and keep it
        foreach (['?q=a:b', '?s=a:b', '#fn:1', '?t=12:30', 'sms:+15551234', 'ftp://example.org/x'] as $u) {
            self::assertSame($u, Safe::url($u), $u);
            self::assertSame('', LatteRuntime::url($u), $u);
        }
        foreach (['/?s=a:b', 'https://example.test/?filter=a:b', 'https://example.test/page/2/', '/shop/?color=a:b'] as $u) {
            self::assertSame($u, LatteRuntime::url($u), $u);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function engines(): iterable
    {
        yield 'latte' => ['latte'];
        yield 'twig' => ['twig'];
    }

    #[DataProvider('engines')]
    public function testSlotPropIsRawOnlyAsCapturedMarkup(string $engine): void
    {
        $src = new LocalSource('ui8kit', self::SRC . '/' . $engine, $engine);
        $r = new BlockRenderer(['ui8kit' => $src], null);
        $tpl = $engine === 'latte' ? 'blocks/section-frame.latte' : 'blocks/section-frame.html.twig';
        foreach (['start', 'end'] as $side) {
            foreach (['split', 'stack'] as $layout) {
                $p = ['title' => 'T', 'layout' => $layout, 'media' => ['show' => true, 'side' => $side], 'aside' => self::XSS];
                $html = $r->render('ui8kit', $tpl, $p);
                self::assertStringNotContainsString('<img src=x', $html, "$engine $layout $side");
                self::assertStringNotContainsString('<script>', $html, "$engine $layout $side");
                self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html, "$engine $layout $side");
                $p['aside'] = ['__html' => '<b data-x="1">captured</b>'];
                self::assertStringContainsString('<b data-x="1">captured</b>', $r->render('ui8kit', $tpl, $p));
            }
        }
    }

    #[DataProvider('engines')]
    public function testRichTextSideNoteNeedsItsMarkup(string $engine): void
    {
        $src = new LocalSource('ui8kit', self::SRC . '/' . $engine, $engine);
        $r = new BlockRenderer(['ui8kit' => $src], null);
        $tpl = $engine === 'latte' ? 'blocks/rich-text.latte' : 'blocks/rich-text.html.twig';
        $base = ['id' => 'rt', 'title' => 'T', 'content' => ['__html' => '<p>Body</p>'], 'layout' => 'sidebar'];
        // the adapter's answer for a blank side note (ReadPath |html, DataSources::trusted after kses): absent
        foreach (['', '  ', "\n\t"] as $blank) {
            self::assertNull(ReadPath::read(['v' => $blank], 'v|html'));
            self::assertNull(DataSources::trusted(['__html' => $blank], false));
            self::assertNull(DataSources::trusted(['__html' => $blank], true));
        }
        $none = $r->render('ui8kit', $tpl, $base + ['aside' => ['title' => 'Note', 'content' => null]]);
        self::assertStringNotContainsString('richtext-aside', $none);
        $some = $r->render('ui8kit', $tpl, $base + ['aside' => ['title' => 'Note', 'content' => ['__html' => '<p>N</p>']]]);
        self::assertStringContainsString('data-testid="richtext-aside"', $some);
        self::assertStringContainsString('<p>N</p>', $some);
    }
}
