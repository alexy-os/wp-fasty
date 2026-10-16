<?php

declare(strict_types=1);

use LatteShop\Cart;
use LatteShop\Money;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/themes/latte-shop/inc/money.php';
require_once dirname(__DIR__) . '/themes/latte-shop/inc/catalog.php';
require_once dirname(__DIR__) . '/themes/latte-shop/inc/cart.php';

final class ShopCartTest extends TestCase
{
    public function testMoneyUsesIntegerCents(): void
    {
        $this->assertSame(1999, Money::toCents('19.99'));
        $this->assertSame(1050, Money::toCents('10,5'));
        $this->assertSame(0, Money::toCents('abc'));
        $this->assertSame(4500, Money::cents('4500'));
        $this->assertSame(4500, Money::cents(4500));
        $this->assertSame(0, Money::cents(''));
        $this->assertSame('$12.30', Money::format(1230));
    }

    public function testCartCookieRoundTripsWhenSigned(): void
    {
        $encoded = Cart::encode([5 => 2, 9 => 1]);
        $this->assertSame([5 => 2, 9 => 1], Cart::decode($encoded));
    }

    public function testTamperedCookieIsRejected(): void
    {
        [$payload, $sig] = explode('.', Cart::encode([5 => 2]));
        $forged = '5:1000';
        $this->assertSame([], Cart::decode($forged . '.' . $sig));
        $this->assertSame([], Cart::decode($payload . '.deadbeef'));
        $this->assertSame([], Cart::decode('garbage'));
    }

    public function testQuantitiesAreClamped(): void
    {
        $this->assertSame([7 => Cart::MAX_QTY], Cart::decode(Cart::encode([7 => 5000, -1 => 3, 8 => 0])));
    }

    public function testShippingIsFreeOverThreshold(): void
    {
        $this->assertSame(500, Cart::shipping(5000));
        $this->assertSame(0, Cart::shipping(10000));
        $this->assertSame(0, Cart::shipping(0));
    }
}
