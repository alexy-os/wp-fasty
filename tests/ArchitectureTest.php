<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Html\ContextHtml;
use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Model\Post;
use WPFasty\Data\Post\PostViewModelBuilder;

/**
 * What Deptrac cannot see: calls to global WordPress functions. Core (ports, DTOs, kernel) and the ViewModel builders
 * (WPFasty\Data, except the ContextFactory facade) take their data through ports, so they call no function that
 * php-stubs/wordpress-stubs declares.
 */
final class ArchitectureTest extends TestCase
{
    private const ROOT = __DIR__.'/../themes/wp-fasty/classes';

    /** Files allowed to talk to WordPress inside the checked namespaces. */
    private const FACADES = ['Data/ContextFactory.php'];

    public function testCoreAndViewModelCallNoWordPressFunction(): void
    {
        $wp = self::wordPressFunctions();
        self::assertArrayHasKey('get_bloginfo', $wp, 'wordpress-stubs not found');
        $calls = [];
        foreach (['Core', 'Data'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::ROOT.'/'.$dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                    continue;
                }
                $rel = substr($file->getPathname(), \strlen(self::ROOT) + 1);
                if (\in_array($rel, self::FACADES, true)) {
                    continue;
                }
                foreach (self::functionCalls((string) file_get_contents($file->getPathname())) as $name) {
                    if (isset($wp[strtolower($name)])) {
                        $calls[] = $rel.': '.$name.'()';
                    }
                }
            }
        }
        self::assertSame([], $calls);
    }

    public function testBuilderTelemetryAndPaletteContractsCallNoWordPressFunction(): void
    {
        $wp = self::wordPressFunctions();
        foreach (['ChatTelemetry', 'AdminPalette'] as $class) {
            $file = dirname(__DIR__).'/plugins/wpfasty-builder/src/'.$class.'.php';
            foreach (self::functionCalls((string) file_get_contents($file)) as $name) {
                self::assertArrayNotHasKey(strtolower($name), $wp, $class.': '.$name);
            }
        }
    }

    public function testDetectorSeesAWordPressCall(): void
    {
        self::assertSame(
            ['get_bloginfo', 'sprintf'],
            self::functionCalls('<?php $a = \get_bloginfo("name"); $o->get_x(); X::y(); function z() {} sprintf("");')
        );
    }

    /** The HTML fields of the context (ContextHtml) are TrustedHtml objects, never strings. */
    public function testContextHtmlFieldsAreTrustedHtml(): void
    {
        $GLOBALS['wp_test_sidebars'] = ['sidebar-1' => '<b>s</b>', 'footer-1' => ''];
        $GLOBALS['wp_test_menus'] = ['locations' => [], 'items' => []];
        $ctx = (require \dirname(__DIR__).'/themes/wp-fasty/configs/context.php')()->createErrorContext();
        foreach (ContextHtml::WIDGET_FIELDS as $k) {
            self::assertInstanceOf(TrustedHtml::class, $ctx['widgets'][$k], "widgets.$k");
        }
        $vm = (new PostViewModelBuilder())->build(new Post(1, 'post', 's', 'T', new TrustedHtml('<p>x</p>'), '', '/', '', ''));
        foreach (ContextHtml::POST_FIELDS as $k) {
            self::assertInstanceOf(TrustedHtml::class, $vm[$k], "post.$k");
        }
        unset($GLOBALS['wp_test_sidebars']);
    }

    /** No builder turns the HTML of a port back into a string, and no view marks a TrustedHtml field raw by hand. */
    public function testNoStringCastOrRawPrintOfContextHtml(): void
    {
        foreach ([...glob(self::ROOT.'/Data/*.php') ?: [], ...glob(self::ROOT.'/Data/*/*.php') ?: []] as $file) {
            $src = (string) file_get_contents($file);
            self::assertDoesNotMatchRegularExpression(
                '/\(string\)\s*\$(post->(content|linkPages)|this->(widgets|comments)->render)/',
                $src,
                basename($file).' casts TrustedHtml to string',
            );
        }
        $views = [];
        foreach (['themes', 'presets'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(\dirname(__DIR__).'/'.$dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                $path = (string) $f;
                if (preg_match('#/(vendor|~cache|node_modules)/#', $path) || !preg_match('/\.(latte|twig)$/', $path)) {
                    continue;
                }
                $views[] = $path;
                $src = (string) file_get_contents($path);
                self::assertDoesNotMatchRegularExpression(
                    '/\{\$(page\[\'(content|linkPages)\'\]|comments|widgets\[\'(sidebar|footer)\'\])\|noescape\}|\{\{\s*(page\.(content|linkPages)|comments|widgets\.(sidebar|footer))\|raw\s*\}\}/',
                    $src,
                    "$path prints a TrustedHtml field with |noescape / |raw",
                );
            }
        }
        self::assertGreaterThan(100, \count($views));
    }

    /** @return list<string> global function names called in $code */
    private static function functionCalls(string $code): array
    {
        $tokens = array_values(array_filter(
            \PhpToken::tokenize($code),
            static fn (\PhpToken $t): bool => !$t->is([\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT]),
        ));
        $names = [];
        foreach ($tokens as $i => $t) {
            if (!$t->is([\T_STRING, \T_NAME_FULLY_QUALIFIED]) || '(' !== ($tokens[$i + 1]->text ?? '')) {
                continue;
            }
            $prev = $tokens[$i - 1] ?? null;
            if (null !== $prev && $prev->is([\T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR, \T_DOUBLE_COLON, \T_FUNCTION, \T_NEW])) {
                continue;
            }
            $names[] = ltrim($t->text, '\\');
        }

        return $names;
    }

    /** @return array<string, true> */
    private static function wordPressFunctions(): array
    {
        $stubs = (string) file_get_contents(__DIR__.'/../vendor/php-stubs/wordpress-stubs/wordpress-stubs.php');
        preg_match_all('/^\s*function\s+&?\s*(\w+)\s*\(/m', $stubs, $m);

        return array_fill_keys(array_map('strtolower', $m[1]), true);
    }
}
