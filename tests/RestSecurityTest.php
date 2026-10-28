<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Rest;

/**
 * Who may call the builder REST routes (wpfasty-builder/v1): the permission_callback of every route for the core
 * roles — anonymous, subscriber, contributor, author, an editor of one page only (another user's page), editor,
 * administrator, a multisite administrator without unfiltered_html — plus publish without publish_pages and the body
 * limit. Capabilities: $GLOBALS['wp_test_caps'] ("cap" = any object, "cap:ID" = that object only).
 */
#[RunTestsInSeparateProcesses]
final class RestSecurityTest extends TestCase
{
    private const PAGE = 7;
    private const OWN_PAGE = 5;
    private const POST = 9;

    /** core role capabilities the routes look at */
    private const ROLES = [
        'anonymous' => [],
        'subscriber' => ['read'],
        'contributor' => ['read', 'edit_posts'],
        'author' => ['read', 'edit_posts', 'publish_posts', 'upload_files'],
        // may edit pages, but only its own one (edit_page maps to edit_others_pages for page 7)
        'page-owner' => ['read', 'edit_pages', 'edit_page:5', 'publish_pages'],
        'editor' => ['read', 'edit_pages', 'edit_page', 'publish_pages', 'edit_others_pages', 'unfiltered_html'],
        'administrator' => ['read', 'edit_pages', 'edit_page', 'publish_pages', 'edit_others_pages', 'unfiltered_html', 'manage_options'],
        // multisite: a site administrator has no unfiltered_html
        'ms-admin' => ['read', 'edit_pages', 'edit_page', 'publish_pages', 'edit_others_pages', 'manage_options'],
    ];

    /** route => roles allowed (anything else must be refused) */
    private const ALLOWED = [
        'GET /catalog' => ['page-owner', 'editor', 'administrator', 'ms-admin'],
        'GET /links' => ['page-owner', 'editor', 'administrator', 'ms-admin'],
        'GET /layout/7' => ['editor', 'administrator', 'ms-admin'],
        'POST /layout/7' => ['editor', 'administrator', 'ms-admin'],
        'GET /layout/5' => ['page-owner', 'editor', 'administrator', 'ms-admin'],
        'GET /layout/9' => [], // not a page
        'POST /render-block page=7' => ['editor', 'administrator', 'ms-admin'],
        'POST /render-block page=5' => ['page-owner', 'editor', 'administrator', 'ms-admin'],
        'POST /render-block' => ['page-owner', 'editor', 'administrator', 'ms-admin'],
        'POST /pages' => ['page-owner', 'editor', 'administrator', 'ms-admin'],
        'POST /sources' => ['administrator'],
    ];

    protected function setUp(): void
    {
        require_once __DIR__ . '/fixtures/rest-stubs.php';
        require_once __DIR__ . '/fixtures/wp-objects-stubs.php';
        foreach (['Rest', 'Sources'] as $f) {
            require_once dirname(__DIR__) . "/plugins/wpfasty-builder/src/$f.php";
        }
        $GLOBALS['wpfasty_wp']['posts'] = [self::PAGE => 'page', self::OWN_PAGE => 'page', self::POST => 'post'];
        $GLOBALS['wp_test_routes'] = [];
        Rest::routes();
    }

    /** @return callable(\WP_REST_Request): bool */
    private static function permission(string $route, string $method): callable
    {
        $r = $GLOBALS['wp_test_routes'][Rest::NS . $route] ?? null;
        self::assertIsArray($r, $route);
        if (!isset($r['permission_callback'])) {
            foreach ($r as $handler) {
                if (is_array($handler) && ($handler['methods'] ?? '') === $method) {
                    $r = $handler;
                }
            }
        }
        self::assertArrayHasKey('permission_callback', $r, "$method $route");
        self::assertNotSame('__return_true', $r['permission_callback'], "$method $route is not public");
        return $r['permission_callback'];
    }

    public function testEveryRouteRefusesWhoMayNotEdit(): void
    {
        $routes = array_keys($GLOBALS['wp_test_routes']);
        self::assertEqualsCanonicalizing(
            array_map(static fn (string $r): string => Rest::NS . $r, ['/catalog', '/links', '/layout/(?P<id>\d+)', '/render-block', '/pages', '/sources']),
            $routes,
            'a new route needs a row in ALLOWED',
        );
        foreach (self::ALLOWED as $call => $allowed) {
            preg_match('#^(GET|POST) (/[a-z-]+)(?:/(\d+))?(?: page=(\d+))?$#', $call, $m);
            $method = $m[1];
            $route = $m[2] === '/layout' ? '/layout/(?P<id>\d+)' : $m[2];
            $params = ($m[3] ?? '') !== '' ? ['id' => (int) $m[3]] : [];
            $json = ($m[4] ?? '') !== '' ? ['page' => (int) $m[4]] : [];
            $perm = self::permission($route, $method);
            foreach (self::ROLES as $role => $caps) {
                $GLOBALS['wp_test_caps'] = $caps;
                $ok = (bool) $perm(new \WP_REST_Request($json, $params));
                self::assertSame(in_array($role, $allowed, true), $ok, "$call as $role");
            }
        }
    }

    public function testPublishNeedsPublishPages(): void
    {
        $GLOBALS['wp_test_caps'] = ['edit_pages', 'edit_page'];
        $res = Rest::saveLayout(new \WP_REST_Request(['publish' => true, 'layout' => []], ['id' => self::PAGE]));
        self::assertInstanceOf(\WP_Error::class, $res);
        self::assertSame('wpfb_forbidden', $res->get_error_code());
        self::assertSame(403, $res->get_error_data()['status']);
    }

    public function testOversizedBodiesAreRefused(): void
    {
        $GLOBALS['wp_test_caps'] = self::ROLES['administrator'];
        $big = ['layout' => ['blocks' => [['props' => ['title' => str_repeat('x', Rest::MAX_BODY)]]]]];
        foreach ([Rest::renderBlock(new \WP_REST_Request($big)), Rest::saveLayout(new \WP_REST_Request($big, ['id' => self::PAGE]))] as $res) {
            self::assertInstanceOf(\WP_Error::class, $res);
            self::assertSame(413, $res->get_error_data()['status']);
        }
    }

    /** remote sources are templates (code): importing one is unfiltered_html + manage_options, also in the admin form */
    public function testSourceImportIsForAdministratorsWithUnfilteredHtml(): void
    {
        foreach (self::ROLES as $role => $caps) {
            $GLOBALS['wp_test_caps'] = $caps;
            self::assertSame($role === 'administrator', \WPFastyBuilder\Sources::canImport(), $role);
        }
    }
}
