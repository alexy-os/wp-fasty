<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Codex\ReadPath;

require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Codex/Format.php';
require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Codex/ReadPath.php';

/**
 * Shared TS↔PHP vectors of the wp-fasty readPath pipes (excerpt-fallback, settings:<key>, in-stock, reading-minutes,
 * term-names:<tax>): tests/fixtures/codex-shared/readpath-wpfasty.json is codegen-x
 * packages/compose-gen/runtimes/core/test/readpath-wpfasty.json (core.ts passes it), readpath-wpfasty-extra.json the
 * wp-fasty cases (term-names: a list only). The TS side: bun tools/codex/readpath-shared.ts --cgx <codegen-x>.
 */
final class CodexReadPathSharedTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, array<string, mixed>}> */
    public static function vectors(): iterable
    {
        $dir = __DIR__ . '/fixtures/codex-shared';
        $main = json_decode((string) file_get_contents($dir . '/readpath-wpfasty.json'), true);
        $extra = json_decode((string) file_get_contents($dir . '/readpath-wpfasty-extra.json'), true);
        foreach ([...$main['cases'], ...$extra['cases']] as $case) {
            yield $case['label'] => [$case, $main['opts']];
        }
    }

    /** "W<n>" stands for n words "w" (as the codegen-x test expands it) */
    private static function expand(mixed $s): mixed
    {
        return is_string($s) && preg_match('/^W(\d+)$/', $s, $m) === 1 ? implode(' ', array_fill(0, (int) $m[1], 'w')) : $s;
    }

    /**
     * @param array<string, mixed> $case
     * @param array<string, mixed> $opts
     */
    #[DataProvider('vectors')]
    public function testVector(array $case, array $opts): void
    {
        $source = (array) $case['source'];
        if (array_key_exists('content', $source)) {
            $source['content'] = self::expand($source['content']);
        }
        self::assertSame($case['want'], ReadPath::read($source, (string) $case['path'], $opts));
    }

    public function testDefaultsMatchCore(): void
    {
        self::assertSame([24, 200], [ReadPath::EXCERPT_WORDS, ReadPath::WORDS_PER_MINUTE]);
        $doc = json_decode((string) file_get_contents(__DIR__ . '/fixtures/codex-shared/readpath-wpfasty.json'), true);
        self::assertGreaterThanOrEqual(40, count($doc['cases']));
    }
}
