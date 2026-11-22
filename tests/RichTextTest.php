<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Codex\ReadPath;

require_once __DIR__.'/fixtures/links-stubs.php';
require_once __DIR__.'/fixtures/rest-stubs.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__).'/plugins/wpfasty-builder/src/'.str_replace('\\', '/', substr($class, 15)).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

/** The Codex `|html` pipe marks trusted markup ({"__html": …}); no Builder block reads it (no data bindings, ADR-007). */
final class RichTextTest extends TestCase
{
    public function testHtmlPipeMarksTrustedMarkup(): void
    {
        self::assertSame(['__html' => '<p>x</p>'], ReadPath::read(['content' => '<p>x</p>'], 'content|html'));
        self::assertSame('<p>x</p>', ReadPath::read(['content' => '<p>x</p>'], 'content'));
        self::assertNull(ReadPath::read(['content' => null], 'content|html'));
    }
}
