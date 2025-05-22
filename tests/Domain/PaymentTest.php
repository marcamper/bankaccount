<?php
declare(strict_types=1);

namespace Tests\Unit\Domain;

use Domain\Currency;
use Domain\Money;
use Domain\Payment;
use Domain\TransactionType;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    public function testPaymentProperties(): void
    {
        $currency = new Currency('PLN');
        $money = new Money(100, $currency);
        $payment = new Payment($money, TransactionType::CREDIT);

        $this->assertEquals($money, $payment->amount());
        $this->assertEquals(TransactionType::CREDIT, $payment->type());
        $this->assertInstanceOf(\DateTimeImmutable::class, $payment->date());
    }
}