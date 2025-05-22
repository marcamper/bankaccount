<?php
declare(strict_types=1);

namespace Tests\Unit\Domain;

use Domain\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function testValidCurrency(): void
    {
        $currency = new Currency('pln');
        $this->assertEquals('PLN', $currency->code());
    }

    public function testInvalidCurrencyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Currency('ABC');
    }
}