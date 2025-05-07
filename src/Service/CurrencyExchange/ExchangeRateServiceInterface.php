<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\CurrencyExchange;

interface ExchangeRateServiceInterface
{
    /**
     * Convert amount from one currency to another.
     *
     * @param string $amount       Amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency   Target currency code
     * @param int    $scale        Number of decimal places in result
     *
     * @return string Converted amount
     */
    public function convert(string $amount, string $fromCurrency, string $toCurrency, int $scale): string;

    /**
     * Get exchange rate between two currencies.
     *
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency   Target currency code
     *
     * @return float Exchange rate
     */
    public function getRate(string $fromCurrency, string $toCurrency): float;
}
