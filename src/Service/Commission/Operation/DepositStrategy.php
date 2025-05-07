<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\Operation;

use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\Commission\User\UserCommissionStrategyInterface;
use Uchman\CommissionTask\Service\Math;

class DepositStrategy extends AbstractOperationStrategy
{
    private const DEPOSIT_COMMISSION_RATE = '0.0003'; // 0.03%

    /**
     * @var array<string, UserCommissionStrategyInterface>
     */
    private array $userTypeStrategies;

    public function __construct(Math $math, array $userTypeStrategies = [])
    {
        parent::__construct($math, 'deposit');
        $this->userTypeStrategies = $userTypeStrategies;
    }

    public function calculate(Transaction $transaction, array $context = []): string
    {
        $userType = $transaction->getUserType();

        // Check if there's a specific user type strategy for deposits
        if (isset($this->userTypeStrategies[$userType])) {
            return $this->userTypeStrategies[$userType]->calculate($transaction, $context);
        }

        // Default deposit commission calculation
        $amount = $transaction->getAmount();
        $commission = $this->math->multiply($amount, self::DEPOSIT_COMMISSION_RATE);

        // Round up to currency's decimal places
        return $this->roundCommission($commission, $transaction->getCurrency());
    }

    private function getCurrencyPrecision(string $currency): int
    {
        $precisions = [
            'EUR' => 2,
            'USD' => 2,
            'JPY' => 0,
            // Add more currencies as needed
        ];

        return $precisions[$currency] ?? 2; // Default to 2 decimal places
    }

    private function roundCommission(string $commission, string $currency): string
    {
        $precision = $this->getCurrencyPrecision($currency);

        return $this->math->ceiling($commission, $precision);
    }
}
