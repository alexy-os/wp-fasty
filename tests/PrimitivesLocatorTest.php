<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Template\Kit;
use WPFasty\Template\LatteRenderer;
use WPFasty\Theme\PrimitivesLocator;

final class PrimitivesLocatorTest extends TestCase
{
    private function theme(): string
    {
        return dirname(__DIR__) . '/themes/latte';
    }

    public function testResolvesUi8Kit3Primitives(): void
    {
        $dir = PrimitivesLocator::resolve($this->theme());
        $this->assertSame($this->theme() . '/ui8kit/ui', $dir);
        $this->assertFileExists($dir . '/button/Button.latte');
        $this->assertTrue(class_exists(\UI8Kit\Latte\Classes::class), 'PSR-4 UI8Kit\\Latte\\ → ui8kit/runtime/');
    }

    public function testLatteRootsPutKitBeforeUi8Kit3(): void
    {
        $this->assertSame(
            [$this->theme() . '/kit/ui', $this->theme() . '/ui8kit/ui'],
            PrimitivesLocator::latteRoots($this->theme()),
        );
    }

    public function testKitComposesPresetsInLegacyOrder(): void
    {
        $this->assertSame(
            'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium '
            . 'ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 '
            . 'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 '
            . 'bg-secondary text-secondary-foreground hover:bg-secondary/80 h-12 rounded-md px-8 w-full',
            Kit::classes('button', ['variant' => 'secondary', 'size' => 'lg'], 'w-full'),
        );
        $this->assertSame('border-b border-border px-4 py-2 mt-2', Kit::classes('card.CardHeader', [], 'mt-2'));
        $this->assertSame('grid grid-cols-1 md:grid-cols-2', Kit::classes('grid', ['cols' => '1-2']));
    }

    public function testMiddleLayerRendersOverUi8Kit3Box(): void
    {
        $cache = sys_get_temp_dir() . '/wpfasty-kit-' . getmypid();
        $tpl = $cache . '/kit-probe';
        @mkdir($tpl, 0775, true);
        file_put_contents($tpl . '/probe.latte', "{include 'block/Block.latte', tag: 'section', id: 'x', class: 'py-8', children: 'Hi'}"
            . "{include 'title/Title.latte', as: 1, children: 'T'}{include 'badge/Badge.latte', variant: 'secondary', children: 'B'}");
        $r2 = new LatteRenderer([$tpl, ...PrimitivesLocator::latteRoots($this->theme())], $cache . '/c');
        $html = (string) preg_replace('/\s+/', ' ', $r2->render('probe'));
        $this->assertStringContainsString('<section class="py-8" id="x"> Hi </section>', $html);
        $this->assertStringContainsString('<h1> T </h1>', $html);
        $this->assertStringContainsString('bg-secondary text-secondary-foreground', $html);
    }
}
