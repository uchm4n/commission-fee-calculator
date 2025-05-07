<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service;

use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\Commission\CommissionCalculator;

class TransactionProcessor
{
    private CommissionCalculator $commissionCalculator;

    public function __construct(CommissionCalculator $commissionCalculator)
    {
        $this->commissionCalculator = $commissionCalculator;
    }

    /**
     * Process CSV file and return commission fees.
     */
    public function processFile(string $filename): array
    {
        $csvData = $this->readCsvFile($filename);

        return $this->processTransactions($csvData);
    }

    /**
     * Read CSV file and return data.
     */
    private function readCsvFile(string $filename): array
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("File not found: {$filename}");
        }

        $csvData = [];
        $handle = fopen($filename, 'r');

        if (false === $handle) {
            throw new \RuntimeException("Unable to open file: {$filename}");
        }

        while (($row = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $csvData[] = $row;
        }

        fclose($handle);

        return $csvData;
    }

    /**
     * Process transactions and calculate commissions.
     */
    private function processTransactions(array $csvData): array
    {
        $commissions = [];
        $context = [];

        foreach ($csvData as $row) {
            if (count($row) < 6) {
                continue; // Skip invalid rows
            }

            $transaction = $this->createTransactionFromCsvRow($row);
            $commission = $this->commissionCalculator->calculate($transaction, $context);
            $commissions[] = $commission;
        }

        return $commissions;
    }

    /**
     * Create a Transaction object from the CSV row.
     */
    private function createTransactionFromCsvRow(array $row): Transaction
    {
        [$date, $userId, $userType, $operationType, $amount, $currency] = $row;

        return new Transaction(
            new \DateTime($date),
            (int) $userId,
            $userType,
            $operationType,
            (string) $amount,
            $currency
        );
    }
}
