<?php
declare(strict_types=1);

namespace Domain;

/**
 * Represents a Currency as ISO 4217 code: PLN, USD, EUR
 * Simple value object with validation
 */
final class Currency
{
    private const ALLOWED = ['PLN', 'USD', 'EUR'];

    private string $code;

    public function __construct(string $code)
    {
        $code = strtoupper($code);
        if (!in_array($code, self::ALLOWED, true)) {
            throw new \InvalidArgumentException("Unsupported currency code: $code");
        }
        $this->code = $code;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}