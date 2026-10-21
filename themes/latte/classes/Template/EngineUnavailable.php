<?php
/**
 * Thrown when a site asks for an engine this parent build does not ship.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

final class EngineUnavailable extends \RuntimeException
{
    public static function for(string $engine): self
    {
        return new self(sprintf(
            'This site renders with %1$s, but this parent theme build ships %2$s only. '
                . 'Install the %1$s parent theme (%1$s.zip).',
            $engine,
            implode(', ', Engines::BUILT),
        ));
    }
}
