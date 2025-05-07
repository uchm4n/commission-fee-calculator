<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\Operation;

use Uchman\CommissionTask\Service\Math;

abstract class AbstractOperationStrategy implements OperationCommissionStrategyInterface
{
    protected Math $math;
    protected string $operationType;

    public function __construct(Math $math, string $operationType)
    {
        $this->math = $math;
        $this->operationType = $operationType;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }
}
