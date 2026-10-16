<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Template\LatteRenderer;
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

    public function testFactoryPicksEngine(): void
    {
        $r = RendererFactory::create('latte', [$this->views()], ['latte' => [dirname(__DIR__) . '/.workspaces/ui8kit-latte/ui']], $this->cache);
        self::assertInstanceOf(LatteRenderer::class, $r);
        self::assertSame($r, RendererFactory::create($r, [], [], $this->cache));
    }
}
