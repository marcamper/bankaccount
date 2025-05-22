<?php
declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Domain\Currency;
use Domain\Money;

class MoneyTest extends TestCase
{
    private Currency $pln;

    protected function setUp(): void
    {
        $this->pln = new Currency('PLN');
    }

    public function testConstructAndToString(): void
    {
        $money = new Money(100.50, $this->pln);
        $this->assertEquals('PLN 100.50', (string)$money);
    }

    public function testAddAndSubtract(): void
    {
        $money1 = new Money(50, $this->pln);
        $money2 = new Money('25.25', $this->pln);

        $sum = $money1->add($money2);
        $this->assertEquals('PLN 75.25', (string)$sum);

        $diff = $money1->subtract($money2);
        $this->assertEquals('PLN 24.75', (string)$diff);
    }

    public function testMultiply(): void
    {
        $money = new Money(100, $this->pln);
        $multiplied = $money->multiply(1.005); // fee 0.5%
        $this->assertEquals('PLN 100.50', (string)$multiplied);
    }

    public function testCurrencyMismatchThrows(): void
    {
        $usd = new Currency('USD');
        $moneyPln = new Money(100, $this->pln);
        $moneyUsd = new Money(100, $usd);

        $this->expectException(\InvalidArgumentException::class);
        $moneyPln->add($moneyUsd);
    }
}