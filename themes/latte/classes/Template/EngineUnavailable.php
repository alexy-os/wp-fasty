<?php

/**
 * Backward-compatible name of EngineUnavailableException (autoloaded by PSR-4 from this file).
 */

declare(strict_types=1);

namespace WPFasty\Template;

/*
 * @deprecated since 2026-11-02 (wp-fasty), use EngineUnavailableException; EngineUnavailable will be removed with the core extraction
 */
class_alias(EngineUnavailableException::class, __NAMESPACE__.'\EngineUnavailable');
