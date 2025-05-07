<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\User;

use Uchman\CommissionTask\Service\Math;

abstract class AbstractUserStrategy implements UserCommissionStrategyInterface
{
    protected Math $math;
    protected string $userType;

    public function __construct(Math $math, string $userType)
    {
        $this->math = $math;
        $this->userType = $userType;
    }

    public function getUserType(): string
    {
        return $this->userType;
    }

    protected function getCurrencyPrecision(string $currency): int
    {
        $precisions = [
            'EUR' => 2,
            'USD' => 2,
            'JPY' => 0,
            // Add more currencies as needed
        ];

        return $precisions[$currency] ?? 2; // Default to 2 decimal places
    }

    protected function roundCommission(string $commission, string $currency): string
    {
        $precision = $this->getCurrencyPrecision($currency);

        return $this->math->ceiling($commission, $precision);
    }
}
