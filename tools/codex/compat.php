<?php
/**
 * Block ↔ type compatibility numbers (DESIGN.md D) with the builder's PHP Compat over real inputs:
 *   php tools/codex/compat.php <codegen-x compose-gen schema/codex dir> [site…] [--detail]
 * Blocks: blocks.json surface kit-block (slots with required paths); field types and the expected taxonomy of a slot
 * kind from its manifest.json. Types: themes/latte-<site>/types/codex.{manifest,delivery}.json. Presentational
 * fields are relaxed (Q6). Read-only.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
foreach (['Format', 'ReadPath', 'Compat', 'Sources'] as $c) {
    require_once "$root/plugins/wpfasty-builder/src/Codex/$c.php";
}

use WPFastyBuilder\Codex\Compat;
use WPFastyBuilder\Codex\Sources;

$args = array_values(array_filter(array_slice($argv, 1), static fn ($a) => $a !== '--detail'));
$detail = in_array('--detail', $argv, true);
$dir = $args[0] ?? fwrite(STDERR, "usage: compat.php <schema/codex dir> [site…] [--detail]\n") && exit(2);
$sites = array_slice($args, 1) ?: ['shop', 'folio', 'press'];
$json = static fn (string $f): array => json_decode((string) file_get_contents($f), true, 512, JSON_THROW_ON_ERROR);

$blocks = array_values(array_filter($json("$dir/blocks.json")['blocks'], static fn ($b) => $b['surface'] === 'kit-block' && !empty($b['data'])));
$types = [];
$wantTax = [];
foreach ($json("$dir/manifest.json")['resources'] as $r) {
    $types[$r['record']['id']] = $r['record']['fields'] ?? [];
    $wantTax[$r['record']['id']] = $r['taxonomies'][0] ?? null;
}
$needs = static function (string $kind, array $required) use ($types): array {
    $out = [];
    foreach ($types[$kind] ?? [] as $f) {
        $p = in_array($f['id'], ['title', 'slug', 'excerpt', 'content'], true) ? $f['id'] : 'metadata.' . $f['id'] . '.value';
        if (in_array($p, $required, true)) {
            $out[$p] = [$f['type'], array_map(static fn ($x) => preg_replace('/^.*\//', '', $x['name']), $f['rules'] ?? [])];
        }
    }
    return $out;
};

$report = [];
foreach ($sites as $site) {
    $t = "$root/themes/latte-$site/types";
    $sources = Sources::fromManifest($json("$t/codex.manifest.json"), $json("$t/codex.delivery.json"));
    $count = [Compat::EXACT => 0, Compat::AUTO => 0, Compat::MANUAL => 0];
    $rows = [];
    foreach ($blocks as $b) {
        $level = Compat::EXACT;
        $slots = [];
        foreach ($b['data'] as $slot) {
            $best = null;
            foreach ($sources as $src) {
                $r = Compat::slot($slot['required'] ?? [], $src, $needs($slot['kind'], $slot['required'] ?? []), $wantTax[$slot['kind']] ?? null);
                $score = [Compat::RANK[$r['level']], count($r['notes'])];
                if ($best === null || $score < $best['score']) {
                    $best = ['score' => $score, 'level' => $r['level'], 'source' => $src['id'], 'notes' => $r['notes']];
                }
            }
            if (Compat::RANK[$best['level']] > Compat::RANK[$level]) {
                $level = $best['level'];
            }
            $slots[] = $slot['prop'] . '<-' . $best['source'] . ':' . $best['level'] . ($best['notes'] ? ' ' . implode(', ', $best['notes']) : '');
        }
        $count[$level]++;
        $rows[$b['spec']] = $level . ' | ' . implode(' ; ', $slots);
    }
    $report[$site] = $count + ($detail ? ['blocks' => $rows] : []);
}
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
