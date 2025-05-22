<?php
declare(strict_types=1);

namespace Tests\Unit\Domain;

use Domain\BankAccount;
use Domain\Currency;
use Domain\Money;
use Domain\Payment;
use Domain\TransactionType;
use Domain\exceptions\InsufficientFundsException;
use Domain\exceptions\CurrencyMismatchException;
use Domain\exceptions\DailyDebitLimitExceededException;
use PHPUnit\Framework\TestCase;

class BankAccountTest extends TestCase
{
    private Currency $pln;
    private BankAccount $account;

    protected function setUp(): void
    {
        $this->pln = new Currency('PLN');
        $this->account = new BankAccount(1, $this->pln);
    }

    public function testInitialBalanceIsZero(): void
    {
        $balance = $this->account->getBalance();
        $this->assertEquals('PLN 0.00', (string)$balance);
    }

    public function testCreditIncreasesBalance(): void
    {
        $payment = new Payment(new Money(100, $this->pln), TransactionType::CREDIT);
        $this->account->credit($payment);

        $balance = $this->account->getBalance();
        $this->assertEquals('PLN 100.00', (string)$balance);
    }

    public function testDebitDecreasesBalanceWithFee(): void
    {
        $this->account->credit(new Payment(new Money(200, $this->pln), TransactionType::CREDIT));

        $debitAmount = new Money(100, $this->pln);
        $debitPayment = new Payment($debitAmount, TransactionType::DEBIT);

        $this->account->debit($debitPayment);

        // Debit + 0.5% fee = 100.50 PLN
        $expectedBalance = new Money(200, $this->pln);
        $expectedBalance = $expectedBalance->subtract($debitAmount->multiply(1.005));

        $this->assertEquals($expectedBalance, $this->account->getBalance());
    }

    public function testDebitWithInsufficientFundsThrows(): void
    {
        $this->account->credit(new Payment(new Money(100, $this->pln), TransactionType::CREDIT));

        $debitPayment = new Payment(new Money(100, $this->pln), TransactionType::DEBIT);

        // 100 + 0.5% fee = 100.50, which is more than balance 100
        $this->expectException(InsufficientFundsException::class);

        $this->account->debit($debitPayment);
    }

    public function testDebitCurrencyMismatchThrows(): void
    {
        $this->account->credit(new Payment(new Money(100, $this->pln), TransactionType::CREDIT));
        $usd = new Currency('USD');

        $debitPayment = new Payment(new Money(10, $usd), TransactionType::DEBIT);

        $this->expectException(CurrencyMismatchException::class);

        $this->account->debit($debitPayment);
    }

    public function testAtMostThreeDebitsPerDay(): void
    {
        $this->account->credit(new Payment(new Money(1000, $this->pln), TransactionType::CREDIT));

        $now = new \DateTimeImmutable('now');

        for ($i = 0; $i < 3; $i++) {
            $payment = new Payment(new Money(10, $this->pln), TransactionType::DEBIT, $now);
            $this->account->debit($payment);
        }

        $this->expectException(DailyDebitLimitExceededException::class);

        $fourthPayment = new Payment(new Money(10, $this->pln), TransactionType::DEBIT, $now);
        $this->account->debit($fourthPayment);
    }
}