<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Entity;

class Transaction
{
    private \DateTime $date;
    private int $userId;
    private string $userType;
    private string $operationType;
    private string $amount;
    private string $currency;

    public function __construct(
        \DateTime $date,
        int $userId,
        string $userType,
        string $operationType,
        string $amount,
        string $currency
    ) {
        $this->date = $date;
        $this->userId = $userId;
        $this->userType = $userType;
        $this->operationType = $operationType;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserType(): string
    {
        return $this->userType;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getWeekIdentifier(): string
    {
        // use ISO-8601 week-numbering year and week number (e.g. "2014-01")
        return $this->date->format('o-W');
    }
}
