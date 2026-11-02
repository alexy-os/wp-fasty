<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__).'/tools/ui8kit3/recipes.php';

/**
 * tools/ui8kit3/presets.php --cgx: a codegen-x recipe equals the pinned one when it has the same keys and values
 * (key order of JSON objects ignored, list order and scalar types significant). Replaces a loose `!=`.
 */
final class Ui8kit3RecipesTest extends TestCase
{
    public function testObjectKeyOrderIsIgnoredAtEveryLevel(): void
    {
        $a = ['base' => 'btn', 'defaults' => ['size' => 'md', 'variant' => 'primary'], 'keys' => ['variant', 'size']];
        $b = ['keys' => ['variant', 'size'], 'defaults' => ['variant' => 'primary', 'size' => 'md'], 'base' => 'btn'];
        self::assertTrue(ui8kit3_same_recipe($a, $b));
    }

    public function testListOrderMatters(): void
    {
        self::assertFalse(ui8kit3_same_recipe(['keys' => ['variant', 'size']], ['keys' => ['size', 'variant']]));
    }

    public function testScalarTypesAreComparedStrictly(): void
    {
        // the loose `!=` it replaces treated these as equal
        self::assertFalse(ui8kit3_same_recipe(['n' => 1], ['n' => '1']));
        self::assertFalse(ui8kit3_same_recipe(['n' => 1], ['n' => 1.0]));
        self::assertFalse(ui8kit3_same_recipe(['n' => null], ['n' => '']));
        self::assertFalse(ui8kit3_same_recipe(['n' => false], ['n' => 0]));
        self::assertTrue(ui8kit3_same_recipe(['n' => 1.5], ['n' => 1.5]));
    }

    public function testJsonDecodedRecipesWithReorderedKeysAreEqual(): void
    {
        $pinned = json_decode('{"base":"x","byKey":{"size":{"sm":"a","md":"b"}},"compound":[]}', true);
        $cgx = json_decode('{"compound":[],"byKey":{"size":{"md":"b","sm":"a"}},"base":"x"}', true);
        self::assertTrue(ui8kit3_same_recipe($pinned, $cgx));
        self::assertFalse(ui8kit3_same_recipe($pinned, json_decode('{"base":"x","byKey":{"size":{"sm":"a"}},"compound":[]}', true)));
    }
}
