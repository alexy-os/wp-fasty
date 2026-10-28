<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Plugin;
use WPFastyBuilder\Previews;
use WPFastyBuilder\Source\LocalSource;

/**
 * Block preview cards (sources/ui8kit/previews, tools/builder/previews.ts): every variant of the bundled catalog gets
 * the URLs of its light and dark WebP in the editor catalog (no dark shot: light only); a source outside the plugin (an imported registry) gets none.
 */
#[RunTestsInSeparateProcesses]
final class PreviewsTest extends TestCase
{
    private string $plugin;

    protected function setUp(): void
    {
        $this->plugin = dirname(__DIR__) . '/plugins/wpfasty-builder';
        $src = $this->plugin . '/src/';
        spl_autoload_register(static function (string $class) use ($src): void {
            if (str_starts_with($class, 'WPFastyBuilder\\')) {
                require_once $src . str_replace('\\', '/', substr($class, 15)) . '.php';
            }
        });
        if (!function_exists('plugins_url')) {
            eval('function plugins_url(string $path = "", string $plugin = ""): string { return "http://example.test/wp-content/plugins/" . basename(dirname($plugin)) . "/" . ltrim($path, "/"); }');
        }
        Plugin::$file = $this->plugin . '/wpfasty-builder.php';
        Plugin::$dir = $this->plugin;
    }

    public function testEveryBundledVariantGetsItsPreview(): void
    {
        foreach (['latte', 'twig'] as $runtime) {
            $src = new LocalSource('ui8kit', $this->plugin . '/sources/ui8kit/' . $runtime, $runtime);
            $blocks = Previews::attach((new Catalog($src->catalog()))->forEditor(null)['blocks'], $src);
            $n = 0;
            foreach ($blocks as $b) {
                foreach ($b['variants'] as $v) {
                    $key = $b['id'] . '/' . $v['id'];
                    $base = 'http://example.test/wp-content/plugins/wpfasty-builder/sources/ui8kit/previews/';
                    $this->assertSame([
                        'src' => $base . $key . '.webp',
                        'dark' => $base . $key . '.dark.webp',
                        'width' => 320, 'height' => 180,
                    ], $v['preview'] ?? null, $key);
                    $this->assertFileExists($this->plugin . '/sources/ui8kit/previews/' . $key . '.webp');
                    $this->assertFileExists($this->plugin . '/sources/ui8kit/previews/' . $key . '.dark.webp');
                    $n++;
                }
            }
            $this->assertSame(474, $n, $runtime);
        }
    }

    public function testAVariantWithoutADarkShotGetsOnlyTheLightOne(): void
    {
        $dir = $this->plugin . '/sources/ui8kit/previews';
        $m = json_decode((string) file_get_contents($dir . '/manifest.json'), true);
        $key = array_key_first($m['variants']);
        unset($m['variants'][$key]['dark']);
        $prop = new \ReflectionProperty(Previews::class, 'manifests');
        $prop->setValue(null, [$dir => $m['variants']]);
        $src = new LocalSource('ui8kit', $this->plugin . '/sources/ui8kit/latte', 'latte');
        [$block, $variant] = explode('/', $key);
        foreach (Previews::attach((new Catalog($src->catalog()))->forEditor(null)['blocks'], $src) as $b) {
            foreach ($b['variants'] as $v) {
                if ($b['id'] === $block && $v['id'] === $variant) {
                    $this->assertArrayNotHasKey('dark', $v['preview']);
                    $this->assertStringEndsWith('/previews/' . $key . '.webp', $v['preview']['src']);
                    return;
                }
            }
        }
        $this->fail('variant ' . $key . ' not in the catalog');
    }

    public function testSourcesOutsideThePluginHaveNoPreviews(): void
    {
        $tmp = sys_get_temp_dir() . '/wpfb-previews-' . getmypid();
        @mkdir($tmp . '/ui8kit/latte', 0777, true);
        @mkdir($tmp . '/ui8kit/previews', 0777, true);
        copy($this->plugin . '/sources/ui8kit/latte/catalog.json', $tmp . '/ui8kit/latte/catalog.json');
        copy($this->plugin . '/sources/ui8kit/previews/manifest.json', $tmp . '/ui8kit/previews/manifest.json');
        $src = new LocalSource('imported', $tmp . '/ui8kit/latte', 'latte');
        $blocks = (new Catalog($src->catalog()))->forEditor(null)['blocks'];
        $this->assertSame($blocks, Previews::attach($blocks, $src));
        $this->assertSame($blocks, Previews::attach($blocks, null));
        $this->assertNull(Previews::dirOf($src));
    }
}
