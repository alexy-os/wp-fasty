<?php

declare(strict_types=1);

// CLI build fixture: official ModulY Signer(null)/Packer. Never reads or generates signing keys.
$runtime = getenv('WPFB_MODULY_RUNTIME');
if (!is_string($runtime) || !is_dir($runtime.'/lib/core/src')) {
    throw new RuntimeException('Set WPFB_MODULY_RUNTIME to the verified published ModulY plugin directory.');
}
spl_autoload_register(static function (string $class) use ($runtime): void {
    $prefix = 'PhpFasty\\Moduly\\';
    if (str_starts_with($class, $prefix)) {
        $file = $runtime.'/lib/core/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
$source = $argv[1] ?? '';
$output = $argv[2] ?? '';
if (!is_dir($source) || '' === $output) {
    throw new InvalidArgumentException('Expected staged module directory and output ZIP.');
}
PhpFasty\Moduly\Signature\Signer::sign($source, null);
PhpFasty\Moduly\Package\Packer::pack($source, $output);
