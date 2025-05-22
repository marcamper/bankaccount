<?php
declare(strict_types=1);

namespace Domain;

use DateTimeImmutable;

/**
 * Payment value object encapsulating money, type and date
 */
final class Payment
{
    private Money $amount;
    private TransactionType $type;
    private DateTimeImmutable $date;

    public function __construct(Money $amount, TransactionType $type, ?DateTimeImmutable $date = null)
    {
        $this->amount = $amount;
        $this->type = $type;
        $this->date = $date ?? new DateTimeImmutable('now');
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function type(): TransactionType
    {
        return $this->type;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }
}