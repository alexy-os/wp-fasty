<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WPFastyTypes\Codex\Canonical;
use WPFastyTypes\Codex\CanonicalError;
use WPFastyTypes\Codex\Json;

require_once dirname(__DIR__) . '/plugins/wpfasty-types/src/Codex/autoload.php';

/** codex-entry/v1 vectors of @ui8kit/contract (tests/fixtures/codex/canonical.json, contract 8299fee). */
final class CodexCanonicalTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>}> */
    public static function vectors(): iterable
    {
        $doc = json_decode((string) file_get_contents(__DIR__ . '/fixtures/codex/canonical.json'), true);
        foreach ($doc['cases'] as $case) {
            yield $case['name'] => [$case];
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('vectors')]
    public function testVector(array $case): void
    {
        if (isset($case['error'])) {
            try {
                $out = Canonical::canonicalize($case['input_text']);
                self::fail("expected {$case['error']}, got {$out}");
            } catch (CanonicalError $e) {
                self::assertSame($case['error'], $e->codexCode, $e->getMessage());
            }
            return;
        }
        self::assertSame($case['canonical'], Canonical::canonicalize($case['input_text']));
        if (isset($case['digest'])) {
            self::assertSame($case['digest'], Canonical::digest($case['input_text']));
        }
    }

    public function testVectorFileShape(): void
    {
        $doc = json_decode((string) file_get_contents(__DIR__ . '/fixtures/codex/canonical.json'), true);
        self::assertSame('codex-entry/v1', $doc['profile']);
        self::assertCount(24, $doc['cases']);
    }

    public function testPhpTraps(): void
    {
        self::assertSame('{"a":{}}', Json::jcs(Json::parse('{"a":{}}')));
        self::assertSame('{"0":"a","1":"b"}', Json::jcs(Json::parse('{"1":"b","0":"a"}')));
        self::assertSame('[1e+21,0,1e-7,12.5,100,-1250]', Json::jcs(Json::parse('[1e21,-0,1e-7,12.50,1E2,-1250]')));
        self::assertSame('0', Json::number(-0.0));
        $this->expectException(CanonicalError::class);
        Json::parse('{"a":1,"a":2}');
    }
}
