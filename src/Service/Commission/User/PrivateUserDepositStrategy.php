<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\User;

use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\Math;

class PrivateUserDepositStrategy extends AbstractUserStrategy
{
    private const DEPOSIT_COMMISSION_RATE = '0.0003'; // 0.03%

    public function __construct(Math $math)
    {
        parent::__construct($math, 'private');
    }

    public function calculate(Transaction $transaction, array $context = []): string
    {
        // Skip if not a deposit operation
        if ('deposit' !== $transaction->getOperationType()) {
            throw new \InvalidArgumentException("Operation type must be 'deposit', got '{$transaction->getOperationType()}'");
        }

        $amount = $transaction->getAmount();
        $commission = $this->math->multiply($amount, self::DEPOSIT_COMMISSION_RATE);

        return $this->roundCommission($commission, $transaction->getCurrency());
    }
}
