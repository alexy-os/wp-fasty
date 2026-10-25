<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Codex\ReadPath;

require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Codex/Format.php';
require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Codex/ReadPath.php';

/** readPath draft vectors generated from the TS reference (tests/fixtures/codex-draft/readpath.json, DRAFT). */
final class CodexReadPathTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, array<string, mixed>}> */
    public static function vectors(): iterable
    {
        $doc = json_decode((string) file_get_contents(__DIR__ . '/fixtures/codex-draft/readpath.json'), true);
        foreach ($doc['cases'] as $case) {
            yield $case['name'] => [$case, $doc['opts']];
        }
    }

    /**
     * @param array<string, mixed> $case
     * @param array<string, mixed> $default
     */
    #[DataProvider('vectors')]
    public function testVector(array $case, array $default): void
    {
        $opts = $case['opts'] === 'default' ? $default : (array) $case['opts'];
        $want = $case['expect'] === ['undefined' => true] ? null : $case['expect'];
        self::assertSame($want, ReadPath::read($case['source'], $case['path'], $opts));
    }
}
