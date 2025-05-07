<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission;

use Uchman\CommissionTask\Entity\Transaction;

interface CommissionStrategyInterface
{
    /**
     * Calculate commission for a transaction.
     *
     * @param Transaction $transaction The transaction to calculate commission for
     * @param array       $context     Optional context data for calculation
     *
     * @return string Commission amount as string
     */
    public function calculate(Transaction $transaction, array $context = []): string;
}
