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

    public function testKitComposesCodegenRecipesInComposeOrder(): void
    {
        // base, every key (selected or default), the matching compound classes, then the extra classes (kit-compose Compose::rc)
        $this->assertSame(
            'inline-flex shrink-0 items-center justify-center whitespace-nowrap font-medium inset-ring transition-colors '
            . 'disabled:pointer-events-none disabled:opacity-50 rounded bg-secondary text-secondary-foreground inset-ring-border '
            . 'h-12 px-6 text-base w-full',
            Kit::classes('button', ['variant' => 'secondary', 'size' => 'lg'], 'w-full'),
        );
        $icon = Kit::classes('button', ['variant' => 'outline', 'layout' => 'icon']);
        $this->assertStringEndsWith('h-10 px-4 text-sm size-10 p-0 [&>svg]:size-5 text-muted-foreground', $icon, 'compound: icon layout');
        $this->assertSame('grid auto-rows-min items-start gap-2 mt-2', Kit::classes('card.CardHeader', [], 'mt-2'));
        $this->assertSame('flex flex-col gap-6 items-start justify-start', Kit::classes('stack', ['gap' => '6']));
        $this->assertSame('grid grid-cols-1 md:grid-cols-2', Kit::classes('grid', ['cols' => '1-2']));
    }

    public function testCodegenRecipesAreThePinnedKitCompose(): void
    {
        // presets/components/<recipe>.json = codegen-x packages/kit-compose/variants at the builder source commit
        $root = dirname(__DIR__);
        $compose = json_decode((string) file_get_contents($root . '/plugins/wpfasty-builder/sources/ui8kit/latte/compose/compose.json'), true);
        foreach (['alert', 'badge', 'button', 'card', 'iconbadge', 'input', 'textarea', 'label', 'link', 'stack', 'title'] as $name) {
            $recipe = Kit::recipe($name);
            $cgx = $compose['recipes'][$name];
            $this->assertSame($cgx['base'], $recipe['base'], $name);
            $this->assertSame($cgx['keys'], $recipe['keys'], $name);
            $this->assertEquals($cgx['byKey'], $recipe['byKey'], $name);
            $this->assertEquals($cgx['compound'] ?? [], $recipe['compound'] ?? [], $name);
        }
    }

    public function testMiddleLayerRendersOverUi8Kit3Box(): void
    {
        $cache = sys_get_temp_dir() . '/wpfasty-kit-' . getmypid();
        $tpl = $cache . '/kit-probe';
        @mkdir($tpl, 0775, true);
        file_put_contents($tpl . '/probe.latte', "{include 'block/Block.latte', tag: 'section', id: 'x', class: 'py-8', children: 'Hi'}"
            . "{include 'title/Title.latte', as: 1, children: 'T'}{include 'badge/Badge.latte', variant: 'secondary', children: 'B'}"
            . "{include 'textarea/Textarea.latte', value: \"  a\\nb\", class: kit('textarea')}");
        $r2 = new LatteRenderer([$tpl, ...PrimitivesLocator::latteRoots($this->theme())], $cache . '/c');
        $html = (string) preg_replace('/\s+/', ' ', $r2->render('probe'));
        $this->assertStringContainsString('<section class="py-8" id="x">Hi</section>', $html);
        $this->assertStringContainsString('<h1 class="text-foreground font-semibold">T</h1>', $html);
        $this->assertMatchesRegularExpression('/<span class="[^"]*rounded-full h-6 px-3 text-xs bg-muted text-foreground[^"]*">B<\/span>/', $html, 'badge: kit-compose span');
        $this->assertStringContainsString('focus-visible:inset-ring-ring rounded min-h-20', $html, 'cn keeps the codegen textarea recipe');
        $this->assertMatchesRegularExpression("/<textarea[^>]*>  a\nb<\/textarea>/", $r2->render('probe'), 'exact value');
    }
}
