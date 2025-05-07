<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\Commission\User;

use Uchman\CommissionTask\Service\Commission\CommissionStrategyInterface;

interface UserCommissionStrategyInterface extends CommissionStrategyInterface
{
    /**
     * Get a user type this strategy handles.
     *
     * @return string User type (e.g., 'private', 'business')
     */
    public function getUserType(): string;
}
