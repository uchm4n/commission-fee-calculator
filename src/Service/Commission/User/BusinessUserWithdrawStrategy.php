<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\User;

use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\Math;

class BusinessUserWithdrawStrategy extends AbstractUserStrategy
{
    private const WITHDRAW_COMMISSION_RATE = '0.005'; // 0.5%

    public function __construct(Math $math)
    {
        parent::__construct($math, 'business');
    }

    public function calculate(Transaction $transaction, array $context = []): string
    {
        // Skip if not a withdrawal operation
        if ('withdraw' !== $transaction->getOperationType()) {
            throw new \InvalidArgumentException("Operation type must be 'withdraw', got '{$transaction->getOperationType()}'");
        }

        $amount = $transaction->getAmount();
        $commission = $this->math->multiply($amount, self::WITHDRAW_COMMISSION_RATE);

        return $this->roundCommission($commission, $transaction->getCurrency());
    }
}
