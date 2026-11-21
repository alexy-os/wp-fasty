<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Simple\SimpleLine;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__).'/plugins/wpfasty-builder/src/'.str_replace('\\', '/', substr($class, 15)).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

/**
 * The bundled UI8Kit Simple snapshot (ADR-007): pinned provenance, the block-contract/v1 contracts, and the static
 * structural prototypes the editor cards frame live over the theme (sandboxed, no scripts).
 */
final class SimplePrototypesTest extends TestCase
{
    private const DIR = __DIR__.'/../plugins/wpfasty-builder/sources/ui8kit';

    public function testProvenanceIsPinnedAndDigested(): void
    {
        $p = self::line()->provenance();
        $this->assertSame('wpfasty-simple-snapshot', $p['format']);
        $this->assertSame('ui8kit@3.5.0', $p['cli']);
        $this->assertSame('buildytech/chat', $p['contracts']['repo']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $p['contracts']['commit']);
        $this->assertSame('ui8kit/prototypes', $p['prototypes']['repo']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $p['prototypes']['commit']);
        $this->assertMatchesRegularExpression('/^sha256:[0-9a-f]{64}$/', $p['digest']);
        // every shipped file is listed with its sha256 (tools/builder/simple-snapshot.ts --check recomputes the digest)
        $snap = json_decode((string) file_get_contents(self::DIR.'/snapshot.json'), true);
        foreach ($snap['files'] as $file => $sha) {
            $this->assertSame($sha, hash_file('sha256', self::DIR.'/'.$file), $file);
        }
        $this->assertArrayNotHasKey('previews', array_flip(scandir(self::DIR) ?: []), 'no image previews');
    }

    public function testEveryContractVariantHasAStructuralPrototype(): void
    {
        $line = self::line();
        $contracts = $line->contracts();
        $this->assertCount(52, $contracts);
        $n = 0;
        foreach ($contracts as $item => $c) {
            $this->assertSame(1, $c['contract']['version'], $item);
            foreach ($line->variants($item) as $variant) {
                ++$n;
                $html = $line->prototype($item, $variant);
                $this->assertNotNull($html, "$item/$variant");
                $this->assertTrue(SimpleLine::wireframe((string) $html), "$item/$variant");
            }
        }
        $this->assertSame(157, $n);
        $this->assertNull($line->prototype('hero-simple', 'nope'));
        $this->assertNull($line->prototype('../x', 'mediaEnd'));
    }

    public function testWireframeAllowlistRejectsActiveContent(): void
    {
        $this->assertTrue(SimpleLine::wireframe('<section class="py-16" data-slot="x"><div aria-hidden="true"></div></section>'));
        foreach ([
            '<script>alert(1)</script>', '<div onclick="x"></div>', '<div style="color:red"></div>', '<a href="#"></a>',
            '<img src="x">', '<div>text</div>', '<!-- c -->', '<iframe></iframe>', '<svg></svg>', '<div title="x"></div>',
        ] as $bad) {
            $this->assertFalse(SimpleLine::wireframe($bad), $bad);
        }
    }

    public function testSheetIsOneTargetablePrototypePerVariantWithoutScripts(): void
    {
        $line = self::line();
        $sheet = (string) $line->sheet('hero-simple');
        $this->assertStringStartsWith('<div class="wpfb-protos" aria-hidden="true">', $sheet);
        foreach ($line->variants('hero-simple') as $variant) {
            $this->assertStringContainsString('id="hero-simple--'.$variant.'"', $sheet);
        }
        $this->assertSame(count($line->variants('hero-simple')), substr_count($sheet, 'class="wpfb-proto"'));
        $this->assertDoesNotMatchRegularExpression('/<(?:scr)ipt|\son[a-z]+=|javascript:|\ssrc=|\shref=/i', $sheet);
        $this->assertNull($line->sheet('no-such-block'));
        $this->assertNull((new SimpleLine(sys_get_temp_dir().'/wpfb-no-snapshot'))->sheet('hero-simple'), 'missing snapshot: no sheet, no error');
    }

    public function testPrototypeSheetIsServedOnlyThroughTheVerifiedPreview(): void
    {
        $front = (string) file_get_contents(dirname(__DIR__).'/plugins/wpfasty-builder/src/Front.php');
        $fn = substr($front, (int) strpos($front, 'public static function prototypes(): void'));
        $fn = substr($fn, 0, (int) strpos($fn, 'public static function view('));
        $this->assertStringContainsString('!self::isPreview($id)', $fn, 'nonce + edit_page capability');
        $this->assertStringContainsString("sanitize_key(wp_unslash(\$_GET['wpfb_prototypes']))", $fn);
        $this->assertStringContainsString('Sources::simple()->sheet($item)', $fn, 'bundled markup only, never request data');
        $this->assertStringContainsString('wp_head()', $fn, 'the theme head: child tokens, fonts, forced scheme');
        $this->assertStringNotContainsString('wp_footer', $fn, 'no footer scripts');
        $css = (string) file_get_contents(dirname(__DIR__).'/plugins/wpfasty-builder/assets/prototypes.css');
        $this->assertStringContainsString('.wpfb-proto:target', $css);
        $this->assertStringContainsString('.wpfb-protos .bg-primary{background-color:var(--primary)}', $css, 'theme tokens');
    }

    private static function line(): SimpleLine
    {
        return new SimpleLine(self::DIR);
    }
}
