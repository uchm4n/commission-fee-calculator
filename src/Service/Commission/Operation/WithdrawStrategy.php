<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\Operation;

use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\Commission\User\UserCommissionStrategyInterface;
use Uchman\CommissionTask\Service\Math;

class WithdrawStrategy extends AbstractOperationStrategy
{
    /**
     * @var array<string, UserCommissionStrategyInterface>
     */
    private array $userTypeStrategies;

    public function __construct(Math $math, array $userTypeStrategies = [])
    {
        parent::__construct($math, 'withdraw');
        $this->userTypeStrategies = $userTypeStrategies;
    }

    public function calculate(Transaction $transaction, array $context = []): string
    {
        $userType = $transaction->getUserType();

        if (!isset($this->userTypeStrategies[$userType])) {
            throw new \InvalidArgumentException("No commission strategy found for user type: {$userType}");
        }

        // Delegate to the appropriate user type strategy
        return $this->userTypeStrategies[$userType]->calculate($transaction, $context);
    }
}
