<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Disable scenario (.cursor/rules/module-disable-scenario.mdc): latte-shop never renders a cart/checkout form when the
 * ModulY cart/orders module is not active — the views read availability flags from the context and show
 * blocks/unavailable instead (Latte and Twig, theme and shop archetype).
 */
final class ShopDisableScenarioTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function roots(): iterable
    {
        yield 'theme' => [dirname(__DIR__) . '/themes/latte-shop'];
        yield 'archetype' => [dirname(__DIR__) . '/presets/archetypes/shop'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('roots')]
    public function testFormsAreGatedByAvailabilityFlags(string $root): void
    {
        $front = (string) file_get_contents($root . '/inc/front.php');
        $this->assertStringContainsString("\$shop['cart_available'] = !empty(\$shop['enabled'])", $front);
        $this->assertStringContainsString("\$shop['checkout_available'] =", $front);
        $this->assertStringContainsString("'enabled' => false", $front, 'default without modules must be off');

        $gates = [
            'pages/cart.latte' => ["{if empty(\$shop['cart_available'])}", "{include 'blocks/unavailable.latte'}", "showCheckout: !empty(\$shop['checkout_available'])"],
            'pages/cart.html.twig' => ['{% if not shop.cart_available %}', "{% include 'blocks/unavailable.html.twig' %}", 'showCheckout: shop.checkout_available ?? false'],
            'pages/checkout.latte' => ["{if empty(\$shop['checkout_available'])}", "{include 'blocks/unavailable.latte'}"],
            'pages/checkout.html.twig' => ['{% if not shop.checkout_available %}', "{% include 'blocks/unavailable.html.twig' %}"],
        ];
        foreach ($gates as $view => $needles) {
            $src = (string) file_get_contents($root . '/views/' . $view);
            foreach ($needles as $n) {
                $this->assertStringContainsString($n, $src, $view);
            }
            // The gate comes before any form posting to admin-post.
            $this->assertLessThan(strpos($src, 'Form.') ?: PHP_INT_MAX, strpos($src, 'unavailable'), $view);
        }
        foreach (['blocks/unavailable.latte', 'blocks/unavailable.html.twig'] as $b) {
            $src = (string) file_get_contents($root . '/views/' . $b);
            $this->assertStringContainsString('back_to_site', $src);
            $this->assertStringNotContainsString('apply_filters', $src);
            $this->assertStringNotContainsString('get_', $src, 'no WP calls in templates');
        }
    }
}
