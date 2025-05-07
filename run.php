<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Uchman\CommissionTask\Service\Commission\CommissionStrategyFactory;
use Uchman\CommissionTask\Service\CurrencyExchange\ExchangeRateService;
use Uchman\CommissionTask\Service\Math;
use Uchman\CommissionTask\Service\TransactionProcessor;

// Check if input file is provided
if ($argc < 2) {
    echo "Usage: php run.php input.csv\n";
    exit(1);
}

$inputFile = $argv[1];

try {
    // Initialize core services
    $math = new Math(10); // High precision for calculations will be rounded later
    $exchangeRateService = ExchangeRateService::getInstance($math);
    
    // Create a strategy factory
    $strategyFactory = new CommissionStrategyFactory($math, $exchangeRateService);
    
    // Create a commission calculator with all required strategies
    $commissionCalculator = $strategyFactory->createCalculator();

    // Process transactions
    $transactionProcessor = new TransactionProcessor($commissionCalculator);
    $commissions = $transactionProcessor->processFile($inputFile);

    // Output results
    foreach ($commissions as $commission) {
        echo $commission . PHP_EOL;
    }
    
    exit(0);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
