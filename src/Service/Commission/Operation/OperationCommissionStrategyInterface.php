<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\Operation;

use Uchman\CommissionTask\Service\Commission\CommissionStrategyInterface;

interface OperationCommissionStrategyInterface extends CommissionStrategyInterface
{
    /**
     * Get an operation type this strategy handles.
     *
     * @return string Operation type (e.g., 'deposit', 'withdraw')
     */
    public function getOperationType(): string;
}
