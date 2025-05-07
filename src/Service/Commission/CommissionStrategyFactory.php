<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission;

use Uchman\CommissionTask\Service\Commission\Operation\DepositStrategy;
use Uchman\CommissionTask\Service\Commission\Operation\OperationCommissionStrategyInterface;
use Uchman\CommissionTask\Service\Commission\Operation\WithdrawStrategy;
use Uchman\CommissionTask\Service\Commission\User\BusinessUserDepositStrategy;
use Uchman\CommissionTask\Service\Commission\User\BusinessUserWithdrawStrategy;
use Uchman\CommissionTask\Service\Commission\User\PrivateUserDepositStrategy;
use Uchman\CommissionTask\Service\Commission\User\PrivateUserWithdrawStrategy;
use Uchman\CommissionTask\Service\Commission\User\UserCommissionStrategyInterface;
use Uchman\CommissionTask\Service\CurrencyExchange\ExchangeRateServiceInterface;
use Uchman\CommissionTask\Service\Math;

/**
 * Factory for creating commission strategies
 * This factory makes it easier to create properly configured strategies
 * and encapsulates the creation logic.
 */
class CommissionStrategyFactory
{
    private Math $math;
    private ExchangeRateServiceInterface $exchangeRateService;

    public function __construct(Math $math, ExchangeRateServiceInterface $exchangeRateService)
    {
        $this->math = $math;
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Create a calculator with all default strategies.
     */
    public function createCalculator(): CommissionCalculator
    {
        return new CommissionCalculator([
            $this->createWithdrawStrategy(),
            $this->createDepositStrategy(),
        ]);
    }

    /**
     * Create a withdrawal strategy with all user type strategies.
     */
    public function createWithdrawStrategy(): OperationCommissionStrategyInterface
    {
        return new WithdrawStrategy($this->math, [
            'private' => $this->createPrivateWithdrawStrategy(),
            'business' => $this->createBusinessWithdrawStrategy(),
        ]);
    }

    /**
     * Create a deposit strategy with all user type strategies.
     */
    public function createDepositStrategy(): OperationCommissionStrategyInterface
    {
        return new DepositStrategy($this->math, [
            'private' => $this->createPrivateDepositStrategy(),
            'business' => $this->createBusinessDepositStrategy(),
        ]);
    }

    /**
     * Create a private withdrawal strategy.
     */
    public function createPrivateWithdrawStrategy(): UserCommissionStrategyInterface
    {
        return new PrivateUserWithdrawStrategy($this->math, $this->exchangeRateService);
    }

    /**
     * Create a business withdraw strategy.
     */
    public function createBusinessWithdrawStrategy(): UserCommissionStrategyInterface
    {
        return new BusinessUserWithdrawStrategy($this->math);
    }

    /**
     * Create a private deposit strategy.
     */
    public function createPrivateDepositStrategy(): UserCommissionStrategyInterface
    {
        return new PrivateUserDepositStrategy($this->math);
    }

    /**
     * Create a business deposit strategy.
     */
    public function createBusinessDepositStrategy(): UserCommissionStrategyInterface
    {
        return new BusinessUserDepositStrategy($this->math);
    }
}
