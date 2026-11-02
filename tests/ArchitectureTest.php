<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;

/**
 * What Deptrac cannot see: calls to global WordPress functions. Core (ports, DTOs, kernel) and the ViewModel builders
 * (WPFasty\Data, except the ContextFactory facade) take their data through ports, so they call no function that
 * php-stubs/wordpress-stubs declares.
 */
final class ArchitectureTest extends TestCase
{
    private const ROOT = __DIR__.'/../themes/latte/classes';

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

    public function testDetectorSeesAWordPressCall(): void
    {
        self::assertSame(
            ['get_bloginfo', 'sprintf'],
            self::functionCalls('<?php $a = \get_bloginfo("name"); $o->get_x(); X::y(); function z() {} sprintf("");')
        );
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
