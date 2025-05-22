<?php
declare(strict_types=1);

namespace Domain;

enum TransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}