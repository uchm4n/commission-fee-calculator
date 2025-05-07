<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\User;

use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\CurrencyExchange\ExchangeRateServiceInterface;
use Uchman\CommissionTask\Service\Math;

class PrivateUserWithdrawStrategy extends AbstractUserStrategy
{
    private const WITHDRAW_COMMISSION_RATE = '0.003'; // 0.3%
    private const FREE_AMOUNT_PER_WEEK_EUR = '1000.00';
    private const FREE_OPERATIONS_PER_WEEK = 3;

    private ExchangeRateServiceInterface $exchangeRateService;
    private array $weeklyWithdrawals = [];

    public function __construct(
        Math $math,
        ExchangeRateServiceInterface $exchangeRateService,
    ) {
        parent::__construct($math, 'private');
        $this->exchangeRateService = $exchangeRateService;
    }

    public function calculate(Transaction $transaction, array $context = []): string
    {
        // Skip if not a withdrawal operation
        if ('withdraw' !== $transaction->getOperationType()) {
            throw new \InvalidArgumentException(
                "Operation type must be 'withdraw', got '{$transaction->getOperationType()}'",
            );
        }

        $userId = $transaction->getUserId();
        $weekId = $transaction->getWeekIdentifier();
        $userWeekKey = $userId.'-'.$weekId;

        // Initialize user's weekly data if not exists
        if (!isset($this->weeklyWithdrawals[$userWeekKey])) {
            $this->weeklyWithdrawals[$userWeekKey] = [
                'operations' => 0,
                'amount_eur' => '0',
            ];
        }

        // Convert amount to EUR for free amount calculation
        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $amountInEur = 'EUR' === $currency
            ? $amount
            : $this->exchangeRateService->convert($amount, $currency, 'EUR', $this->math->getScale());

        // Update weekly stats
        ++$this->weeklyWithdrawals[$userWeekKey]['operations'];
        $operationCount = $this->weeklyWithdrawals[$userWeekKey]['operations'];

        // Get current week's total BEFORE this operation
        $totalWithdrawnEur = $this->weeklyWithdrawals[$userWeekKey]['amount_eur'];
        $remainingFreeAmount = $this->math->subtract(self::FREE_AMOUNT_PER_WEEK_EUR, $totalWithdrawnEur);

        // Apply free amount logic
        if ($operationCount < self::FREE_OPERATIONS_PER_WEEK && $this->math->compare($remainingFreeAmount, '0') > 0) {
            // Free amount is not exhausted
            if ($this->math->compare($amountInEur, $remainingFreeAmount) <= 0) {
                // All operations are within free limit
                // Add to user's total after handling logic
                $this->weeklyWithdrawals[$userWeekKey]['amount_eur'] = $this->math->add(
                    $totalWithdrawnEur,
                    $amountInEur,
                );

                return $this->roundCommission('0', $currency);
            }

            // Otherwise, partially exceeds the limit
            // Calculate the exceeding amount in EUR
            $exceededAmountEur = $this->math->subtract($amountInEur, $remainingFreeAmount);

            // Convert the exceeded amount back to the original currency
            $exceededAmount = 'EUR' === $currency
                ? $exceededAmountEur
                : $this->exchangeRateService->convert($exceededAmountEur, 'EUR', $currency, $this->math->getScale());

            // Calculate commission on the chargeable portion in the original currency
            $commission = $this->math->multiply($exceededAmount, self::WITHDRAW_COMMISSION_RATE);

            // Add full operation to user's stats AFTER calculation
            $this->weeklyWithdrawals[$userWeekKey]['amount_eur'] = $this->math->add($totalWithdrawnEur, $amountInEur);

            return $this->roundCommission($commission, $currency);
        }

        // Regular commission calculation for operations beyond the free limit
        $commission = $this->math->multiply($amount, self::WITHDRAW_COMMISSION_RATE);

        return $this->roundCommission($commission, $currency);
    }
}
