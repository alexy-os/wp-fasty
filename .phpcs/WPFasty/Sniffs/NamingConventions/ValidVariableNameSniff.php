<?php

declare(strict_types=1);

namespace WPFasty\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Common;

/**
 * camelCase for our variables, parameters and properties (Symfony), like Zend.NamingConventions.ValidVariableName,
 * but WordPress-aware:
 *  - WordPress globals ($wp_query, $wpdb, $post_type, …) keep their names (`allowedGlobals`);
 *  - properties read on other objects ($post->post_title, $term->term_id, $wpdb->num_queries) are WordPress
 *    fields and are not checked; `$this->…` / `self::$…` and every declared property are.
 */
final class ValidVariableNameSniff extends AbstractVariableSniff
{
    /** @var list<string> WordPress globals (and the few core variables a template/eval-file scope receives) */
    public array $allowedGlobals = [
        'wp_query', 'wp_the_query', 'wpdb', 'wp_rewrite', 'wp_filter', 'wp_actions', 'wp_current_filter',
        'wp_version', 'wp_locale', 'wp_scripts', 'wp_styles', 'wp_admin_bar', 'wp_customize', 'wp_roles',
        'wp_meta_boxes', 'wp_post_types', 'wp_taxonomies', 'wp_registered_sidebars', 'wp_registered_widgets',
        'wp_theme_directories', 'wp_embed', 'wp_object_cache', 'wp_widget_factory', 'wp_rest_server',
        'current_screen', 'post_type', 'typenow', 'pagenow', 'hook_suffix', 'plugin_page', 'content_width',
        'shortcode_tags', 'allowedposttags', 'allowedtags', 'wp_test_flags',
    ];

    protected function processVariable(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $name = ltrim($tokens[$stackPtr]['content'], '$');
        if ('this' === $name) {
            $this->checkThisProperty($phpcsFile, $stackPtr);

            return;
        }
        if (isset($this->phpReservedVars[$name]) || in_array($name, $this->allowedGlobals, true)) {
            return;
        }
        $this->check($phpcsFile, $stackPtr, $name, 'NotCamelCaps');
    }

    protected function processMemberVar(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $this->check($phpcsFile, $stackPtr, ltrim($tokens[$stackPtr]['content'], '$'), 'MemberVarNotCamelCaps');
    }

    protected function processVariableInString(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if (0 === preg_match_all('/(?<!\\\\)\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $tokens[$stackPtr]['content'], $m)) {
            return;
        }
        foreach ($m[1] as $name) {
            if ('this' !== $name && !isset($this->phpReservedVars[$name]) && !in_array($name, $this->allowedGlobals, true)) {
                $this->check($phpcsFile, $stackPtr, $name, 'StringVarNotCamelCaps');
            }
        }
    }

    private function checkThisProperty(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $op = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if (false === $op || !in_array($tokens[$op]['code'], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
            return;
        }
        $prop = $phpcsFile->findNext(T_WHITESPACE, $op + 1, null, true);
        if (false === $prop || T_STRING !== $tokens[$prop]['code']) {
            return;
        }
        $next = $phpcsFile->findNext(T_WHITESPACE, $prop + 1, null, true);
        if (false !== $next && T_OPEN_PARENTHESIS === $tokens[$next]['code']) {
            return; // method call
        }
        $this->check($phpcsFile, $prop, $tokens[$prop]['content'], 'NotCamelCaps');
    }

    private function check(File $phpcsFile, int $ptr, string $name, string $code): void
    {
        if (!Common::isCamelCaps(ltrim($name, '_'), false, true, false)) {
            $phpcsFile->addError('Variable "%s" is not in valid camel caps format', $ptr, $code, [$name]);
        }
    }
}
