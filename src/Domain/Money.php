<?php
declare(strict_types=1);

namespace Domain;

/**
 * Represents monetary amount with currency
 * Internally amount stored as int (centy/grosze)
 */
final class Money
{
    private int $amount; // in minor units, e.g. grosze/cents
    private Currency $currency;

    /**
     * @param float|string $amount Decimal amount, e.g. 10.50 or "100.25"
     * @param Currency $currency
     */
    public function __construct(float|string $amount, Currency $currency)
    {
        // normalize to int minor unit
        if (is_string($amount)) {
            if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
                throw new \InvalidArgumentException("Invalid money amount format: $amount");
            }
            $amount = (float)$amount;
        }
        $this->amount = (int)round($amount * 100);
        $this->currency = $currency;
    }

    /**
     * Create from minor units directly
     */
    public static function fromMinor(int $minorUnits, Currency $currency): self
    {
        $obj = new self(0, $currency);
        $obj->amount = $minorUnits;
        return $obj;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return self::fromMinor($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        return self::fromMinor($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        $newAmount = (int) round($this->amount * $multiplier);
        return self::fromMinor($newAmount, $this->currency);
    }

    public function greaterOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount >= $other->amount;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency->equals($other->currency);
    }

    private function assertSameCurrency(self $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
    }

    public function __toString(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->amount / 100);
    }
}