<?php
declare(strict_types=1);

namespace Domain;

use Domain\TransactionType;
use Domain\Money;
use Domain\Payment;
use Domain\exceptions\InsufficientFundsException;
use Domain\exceptions\CurrencyMismatchException;
use Domain\exceptions\DailyDebitLimitExceededException;

final class BankAccount
{
    private int $id;
    private Currency $currency;
    /** @var Payment[] */
    private array $payments = [];

    private const TRANSACTION_FEE_RATE = 0.005; // 0.5%

    public function __construct(int $id, Currency $currency)
    {
        $this->id = $id;
        $this->currency = $currency;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    /**
     * @return Payment[]
     */
    public function payments(): array
    {
        return $this->payments;
    }

    public function credit(Payment $payment): void
    {
        $this->assertSameCurrency($payment);
        if ($payment->type() !== TransactionType::CREDIT) {
            throw new \InvalidArgumentException('Payment type must be CREDIT for credit operation.');
        }
        $this->payments[] = $payment;
    }

    public function debit(Payment $payment): void
    {
        $this->assertSameCurrency($payment);
        if ($payment->type() !== TransactionType::DEBIT) {
            throw new \InvalidArgumentException('Payment type must be DEBIT for debit operation.');
        }

        $paymentWithFee = $this->applyTransactionFee($payment);

        if (!$this->canDebit($paymentWithFee)) {
            throw new InsufficientFundsException('Insufficient funds for this debit including transaction fee.');
        }

        if ($this->countDebitsToday($payment->date()) >= 3) {
            throw new DailyDebitLimitExceededException('Exceeded max 3 debit transactions per day.');
        }

        // Store payment with fee applied
        $this->payments[] = new Payment($paymentWithFee, TransactionType::DEBIT, $payment->date());
    }

    /**
     * Calculate current balance from all payments credit and debit with fees
     * credit adds money, debit subtracts money + fees
     */
    public function getBalance(): Money
    {
        $balance = Money::fromMinor(0, $this->currency);

        foreach ($this->payments as $payment) {
            $amount = $payment->amount();
            if ($payment->type() === TransactionType::CREDIT) {
                $balance = $balance->add($amount);
            } else { // DEBIT
                $balance = $balance->subtract($amount);
            }
        }

        return $balance;
    }

    /**
     * Apply 0.5% fee on debit payment, round up to nearest cent
     */
    private function applyTransactionFee(Payment $payment): Money
    {
        $amount = $payment->amount();

        // Multiply by 1 + 0.5% fee
        $amountWithFee = $amount->multiply(1 + self::TRANSACTION_FEE_RATE);

        return $amountWithFee;
    }

    private function canDebit(Money $amountWithFee): bool
    {
        return $this->getBalance()->greaterOrEqual($amountWithFee);
    }

    private function countDebitsToday(\DateTimeInterface $date): int
    {
        $count = 0;
        $dateString = $date->format('Y-m-d');
        foreach ($this->payments as $payment) {
            if (
                $payment->type() === TransactionType::DEBIT &&
                $payment->date()->format('Y-m-d') === $dateString
            ) {
                $count++;
            }
        }
        return $count;
    }

    private function assertSameCurrency(Payment $payment): void
    {
        if (!$payment->amount()->currency()->equals($this->currency)) {
            throw new CurrencyMismatchException('Payment currency does not match account currency.');
        }
    }
}