<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission;

use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\Commission\Operation\OperationCommissionStrategyInterface;

class CommissionCalculator
{
    /**
     * @var array<string, OperationCommissionStrategyInterface>
     */
    private array $strategies = [];

    /**
     * @param OperationCommissionStrategyInterface[] $strategies
     */
    public function __construct(array $strategies = [])
    {
        foreach ($strategies as $strategy) {
            $this->addStrategy($strategy);
        }
    }

    public function addStrategy(OperationCommissionStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getOperationType()] = $strategy;
    }

    public function calculate(Transaction $transaction, array $context = []): string
    {
        $operationType = $transaction->getOperationType();

        if (!isset($this->strategies[$operationType])) {
            throw new \InvalidArgumentException("No commission strategy found for operation type: {$operationType}");
        }

        return $this->strategies[$operationType]->calculate($transaction, $context);
    }
}
