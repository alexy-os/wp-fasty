<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Template\EngineUnavailableException;
use WPFastyTypes\Codex\CanonicalErrorException;

/** The pre-Symfony exception names stay loadable (class_alias, @deprecated) and catch the new exceptions. */
final class DeprecatedExceptionAliasTest extends TestCase
{
    public function testEngineUnavailableIsAnAliasOfEngineUnavailableException(): void
    {
        $this->assertTrue(class_exists('WPFasty\Template\EngineUnavailable'));
        try {
            throw EngineUnavailableException::for('twig');
        } catch (\WPFasty\Template\EngineUnavailable $e) {
            $this->assertInstanceOf(EngineUnavailableException::class, $e);
        }
    }

    public function testCanonicalErrorIsAnAliasOfCanonicalErrorException(): void
    {
        require_once dirname(__DIR__).'/plugins/wpfasty-types/src/Codex/autoload.php';
        $this->assertTrue(class_exists('WPFastyTypes\Codex\CanonicalError'));
        $e = new \WPFastyTypes\Codex\CanonicalError('canonical.x', '$', 'm');
        $this->assertInstanceOf(CanonicalErrorException::class, $e);
        $this->assertSame('canonical.x: m (at $)', $e->getMessage());
    }
}
