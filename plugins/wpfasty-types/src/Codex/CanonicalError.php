<?php

/**
 * Backward-compatible name of CanonicalErrorException (loaded by src/Codex/autoload.php from this file).
 */

declare(strict_types=1);

namespace WPFastyTypes\Codex;

/*
 * @deprecated since 2026-11-02 (wp-fasty), use CanonicalErrorException; CanonicalError will be removed with the core extraction
 */
class_alias(CanonicalErrorException::class, __NAMESPACE__.'\CanonicalError');
