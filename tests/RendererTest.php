<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Template\LatteRenderer;
use WPFasty\Template\EngineUnavailable;
use WPFasty\Template\Engines;
use WPFasty\Template\RendererFactory;
use WPFasty\Template\TwigRenderer;
use WPFasty\Theme\PrimitivesLocator;

final class RendererTest extends TestCase
{
    private string $cache;

    protected function setUp(): void
    {
        $this->cache = sys_get_temp_dir() . '/renderer-' . uniqid('', true);
    }

    private function views(): string
    {
        return __DIR__ . '/fixtures/views';
    }

    private function latte(): LatteRenderer
    {
        return new LatteRenderer([$this->views(), dirname(__DIR__) . '/.workspaces/ui8kit-latte/ui'], $this->cache);
    }

    private function twig(): TwigRenderer
    {
        $prims = PrimitivesLocator::resolveTwig(dirname(__DIR__) . '/themes/latte');
        if ($prims === null || !class_exists(\Twig\Environment::class)) {
            $this->markTestSkipped('Twig primitives not generated (run bun run codegen:verify)');
        }
        return new TwigRenderer([$this->views(), $prims], $this->cache . '/twig');
    }

    private static function normalize(string $html): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $html));
    }

    public function testLatteRendersViewNameWithoutExtension(): void
    {
        $r = $this->latte();
        self::assertSame('latte', $r->engine());
        self::assertTrue($r->exists('probe'));
        self::assertFalse($r->exists('missing'));
        self::assertStringContainsString('<p>&lt;b&gt;</p>', $r->render('probe', ['title' => '<b>']));
    }

    public function testTwigAndLattePrimitivesProduceIdenticalDom(): void
    {
        $twig = $this->twig();
        $ctx = ['title' => '<b>'];
        self::assertSame(
            self::normalize($this->latte()->render('probe', $ctx)),
            self::normalize($twig->render('probe', $ctx))
        );
    }

    public function testTwigSafeUrlMatchesLatteUrlCheck(): void
    {
        foreach (['https://a.test/x?y=1', '/rel', '#frag', '?q=1', 'mailto:a@b.c', 'page'] as $ok) {
            self::assertSame($ok, TwigRenderer::safeUrl($ok));
            self::assertSame($ok, \Latte\Essential\Filters::checkUrl($ok));
        }
        foreach (['javascript:alert(1)', 'data:text/html,x', 'vbscript:x'] as $bad) {
            self::assertSame('', TwigRenderer::safeUrl($bad));
            self::assertSame('', \Latte\Essential\Filters::checkUrl($bad));
        }
    }

    public function testBothEnginesShareTheHelperVocabulary(): void
    {
        $twig = $this->twig()->twig();
        $latte = $this->latte()->latte();
        foreach (\WPFasty\Template\ViewHelpers::FILTERS as $f) {
            self::assertNotFalse($twig->getFilter($f), "twig filter $f");
            self::assertArrayHasKey($f, $latte->getFilters(), "latte filter $f");
        }
        foreach (\WPFasty\Template\ViewHelpers::FUNCTIONS as $fn) {
            self::assertNotFalse($twig->getFunction($fn), "twig function $fn");
        }
        self::assertArrayHasKey('icon', $latte->getFunctions());
        self::assertArrayHasKey('t', $latte->getFunctions());
        foreach (['https://a.test/?a=1&b=<2>', 'javascript:alert(1)'] as $url) {
            $ctx = ['url' => $url, 'html' => '<b>ok</b><script>x</script>'];
            self::assertSame(
                self::normalize($this->latte()->render('helpers', $ctx)),
                self::normalize($this->twig()->render('helpers', $ctx))
            );
        }
    }

    public function testNoFallbackBetweenEngines(): void
    {
        // Isolated runtimes (K7): an engine without its primitives (or library) is an error, never the other engine.
        $latte = ['latte' => [dirname(__DIR__) . '/.workspaces/ui8kit-latte/ui'], 'twig' => []];
        try {
            RendererFactory::create('twig', [$this->views()], $latte, $this->cache);
            self::fail('twig without Twig primitives must not render');
        } catch (EngineUnavailable $e) {
            self::assertStringContainsString('twig', $e->getMessage());
        }
        $this->expectException(EngineUnavailable::class);
        RendererFactory::create('latte', [$this->views()], ['latte' => [], 'twig' => ['x']], $this->cache);
    }

    public function testUnknownEngineIsUnavailable(): void
    {
        self::assertFalse(Engines::available('blade'));
        self::assertSame(['latte', 'twig'], Engines::BUILT, 'the repo parent ships both; release builds rewrite this');
        $this->expectException(EngineUnavailable::class);
        RendererFactory::create('blade', [], ['blade' => ['x']], $this->cache);
    }

    public function testFactoryPicksEngine(): void
    {
        $r = RendererFactory::create('latte', [$this->views()], ['latte' => [dirname(__DIR__) . '/.workspaces/ui8kit-latte/ui']], $this->cache);
        self::assertInstanceOf(LatteRenderer::class, $r);
        self::assertSame($r, RendererFactory::create($r, [], [], $this->cache));
    }
}
