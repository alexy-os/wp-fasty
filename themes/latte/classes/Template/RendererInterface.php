<?php
/**
 * Engine-agnostic renderer contract (ADR-001 §1).
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

/**
 * View names are given without extension (e.g. `pages/single`); each renderer
 * appends its own and resolves through the shared root chain
 * (child views → parent views → primitives).
 */
interface RendererInterface extends TemplateEngineInterface
{
    /** Engine id: `latte` or `twig`. */
    public function engine(): string;

    /** File extension appended to view names, e.g. `.latte`. */
    public function extension(): string;

    /** Whether a view exists in any root. */
    public function exists(string $view): bool;
}
