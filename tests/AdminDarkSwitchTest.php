<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Admin;

/**
 * The editor's dark mode switch (Admin::darkSwitch): the last node of the toolbar's secondary group, only on the
 * Builder screen with a page open in the editor; an accessible toggle button with the Lucide moon and sun.
 */
#[RunTestsInSeparateProcesses]
final class AdminDarkSwitchTest extends TestCase
{
    protected function setUp(): void
    {
        $src = dirname(__DIR__) . '/plugins/wpfasty-builder/src/';
        spl_autoload_register(static function (string $class) use ($src): void {
            if (str_starts_with($class, 'WPFastyBuilder\\')) {
                require_once $src . str_replace('\\', '/', substr($class, 15)) . '.php';
            }
        });
        if (!class_exists('WP_Admin_Bar', false)) {
            eval('class WP_Admin_Bar { public array $nodes = []; public function add_node(array $n): void { $this->nodes[] = $n; } }');
        }
        if (!function_exists('get_current_screen')) {
            eval('function get_current_screen(): ?object { return $GLOBALS["wpfb_test_screen"] ?? null; }');
        }
    }

    private function state(string $hook, bool $editor, ?string $screen): void
    {
        (new \ReflectionProperty(Admin::class, 'hook'))->setValue(null, $hook);
        (new \ReflectionProperty(Admin::class, 'editor'))->setValue(null, $editor);
        $GLOBALS['wpfb_test_screen'] = $screen === null ? null : (object) ['id' => $screen];
    }

    /** @return list<array<string, mixed>> */
    private function nodes(): array
    {
        $bar = new \WP_Admin_Bar();
        Admin::darkSwitch($bar);
        return $bar->nodes;
    }

    public function testTheSwitchIsTheLastToolbarNodeOnTheBuilderScreen(): void
    {
        $this->state('pages_page_wpfasty-builder', true, 'pages_page_wpfasty-builder');
        $nodes = $this->nodes();
        $this->assertCount(1, $nodes);
        $this->assertSame(Admin::DARK, $nodes[0]['id']);
        $this->assertSame('top-secondary', $nodes[0]['parent']);
        $this->assertFalse($nodes[0]['href']);
        $this->assertGreaterThan(9999, Admin::DARK_PRIORITY, 'after every core toolbar item');
    }

    public function testNoSwitchOnOtherScreensOrWithoutTheEditor(): void
    {
        $this->state('pages_page_wpfasty-builder', true, 'edit-page');
        $this->assertSame([], $this->nodes(), 'another admin screen');
        $this->state('pages_page_wpfasty-builder', false, 'pages_page_wpfasty-builder');
        $this->assertSame([], $this->nodes(), 'the page chooser of the Builder screen (no editor)');
        $this->state('pages_page_wpfasty-builder', true, null);
        $this->assertSame([], $this->nodes(), 'no screen');
    }

    public function testTheButtonIsAnAccessibleToggleWithMoonAndSun(): void
    {
        $html = Admin::darkButton();
        $this->assertStringContainsString('<button type="button" id="wpfb-dark-toggle"', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
        $this->assertStringContainsString('title="Dark mode"', $html);
        $this->assertStringContainsString('<span class="screen-reader-text">Dark mode</span>', $html);
        $this->assertSame(2, substr_count($html, '<svg '));
        $this->assertSame(2, substr_count($html, 'aria-hidden="true"'));
        $this->assertStringContainsString('class="wpfb-dark-moon"', $html);
        $this->assertStringContainsString('class="wpfb-dark-sun"', $html);
    }
}
