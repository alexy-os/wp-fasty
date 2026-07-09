/**
 * Emits the PHP side of the parity toolchain: `composer.json` (Latte + Twig
 * + the UI8Kit autoload) and two batch render harnesses mirroring
 * `cmd/parity/main.go` — stdin JSON cases in, stdout JSON HTML out.
 */

import { BANNER, type GeneratedFile } from "./common";

export function emitComposerJson(): GeneratedFile {
  const composer = {
    description: "Generated UI8Kit PHP runtimes (Latte + Twig). Do not edit.",
    // vendor-dir lives under php/ so Go tooling never mistakes it for Go
    // vendoring (generated/ also hosts the Templ module).
    config: { "vendor-dir": "php/vendor" },
    require: {
      php: ">=8.1",
      "latte/latte": "^3.0",
      "twig/twig": "^3.0",
    },
    autoload: {
      "psr-4": { "UI8Kit\\": "php/UI8Kit/" },
    },
  };
  return { path: "composer.json", contents: JSON.stringify(composer, null, 2) + "\n" };
}

/** Latte-only composer.json for theme bundles (no Twig, no parity harness). */
export function emitLatteBundleComposerJson(): GeneratedFile {
  const composer = {
    name: "ui8kit/latte-primitives",
    description: "Generated UI8Kit Latte primitives. Do not edit — regenerate via @ui8kit/codegen.",
    type: "library",
    license: "MIT",
    config: { "vendor-dir": "php/vendor" },
    require: {
      php: ">=8.1",
      "latte/latte": "^3.0",
    },
    autoload: {
      "psr-4": { "UI8Kit\\": "php/UI8Kit/" },
    },
  };
  return { path: "composer.json", contents: JSON.stringify(composer, null, 2) + "\n" };
}

export function emitLatteParityHarness(): GeneratedFile {
  const contents = `<?php

// ${BANNER}
//
// Command parity-latte renders generated .latte parts from canonical JSON.
// stdin:  [{"template":"button/Button.latte","props":{...},"attrs":{},"children":"x"}]
// stdout: ["<button ...>...</button>", ...]

declare(strict_types=1);

require __DIR__ . '/../../php/vendor/autoload.php';

$engine = new Latte\\Engine();
$tmp = sys_get_temp_dir() . '/ui8kit-latte-' . md5(__DIR__);
if (!is_dir($tmp)) {
    mkdir($tmp, 0777, true);
}
$engine->setTempDirectory($tmp);
$engine->setLoader(new Latte\\Loaders\\FileLoader(__DIR__ . '/../../ui'));

$cases = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$out = [];
foreach ($cases as $case) {
    $params = (array) ($case['props'] ?? []);
    $params['attrs'] = (array) ($case['attrs'] ?? []);
    $params['children'] = (string) ($case['children'] ?? '');
    $out[] = $engine->renderToString($case['template'], $params);
}
echo json_encode($out, JSON_THROW_ON_ERROR);
`;
  return { path: "cmd/parity-latte/main.php", contents };
}

export function emitTwigParityHarness(): GeneratedFile {
  const contents = `<?php

// ${BANNER}
//
// Command parity-twig renders generated .html.twig parts from canonical JSON.
// stdin:  [{"template":"button/Button.html.twig","props":{...},"attrs":{},"children":"x"}]
// stdout: ["<button ...>...</button>", ...]

declare(strict_types=1);

require __DIR__ . '/../../php/vendor/autoload.php';

$twig = new Twig\\Environment(
    new Twig\\Loader\\FilesystemLoader(__DIR__ . '/../../ui'),
    ['autoescape' => 'html', 'strict_variables' => false, 'cache' => false]
);
$twig->addExtension(new UI8Kit\\TwigExtension());

$cases = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$out = [];
foreach ($cases as $case) {
    $params = (array) ($case['props'] ?? []);
    $params['attrs'] = (array) ($case['attrs'] ?? []);
    $params['children'] = (string) ($case['children'] ?? '');
    $out[] = $twig->render($case['template'], $params);
}
echo json_encode($out, JSON_THROW_ON_ERROR);
`;
  return { path: "cmd/parity-twig/main.php", contents };
}
