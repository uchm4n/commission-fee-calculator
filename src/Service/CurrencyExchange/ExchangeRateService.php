<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service\CurrencyExchange;

use Uchman\CommissionTask\Service\EnvLoader;
use Uchman\CommissionTask\Service\Math;

class ExchangeRateService implements ExchangeRateServiceInterface
{
    private static ?ExchangeRateService $instance = null;
    private array $rates = [];
    private string $baseCurrency = 'EUR';
    private Math $math;
    private EnvLoader $envLoader;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct(Math $math)
    {
        $this->math = $math;
        $this->envLoader = EnvLoader::getInstance();
        $this->loadRates();
    }

    /**
     * Get a singleton instance.
     */
    public static function getInstance(Math $math): self
    {
        if (null === self::$instance) {
            self::$instance = new self($math);
        }

        return self::$instance;
    }

    /**
     * Reset instance (mainly for testing purposes).
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Convert amount from one currency to another.
     */
    public function convert(string $amount, string $fromCurrency, string $toCurrency, int $scale): string
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getRate($fromCurrency, $toCurrency);

        return $this->math->multiply($amount, (string) $rate);
    }

    /**
     * Get exchange rate between two currencies.
     */
    public function getRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $fromRate = $this->rates[$fromCurrency] ?? null;
        $toRate = $this->rates[$toCurrency] ?? null;

        if (null === $fromRate || null === $toRate) {
            throw new \RuntimeException(
                "Exchange rate not available for conversion from {$fromCurrency} to {$toCurrency}"
            );
        }

        // Return the correct rate based on a currency direction
        return match (true) {
            $fromCurrency === $this->baseCurrency => $toRate,             // EUR→XXX: use destination rate
            $toCurrency === $this->baseCurrency => 1 / $fromRate,         // XXX→EUR: invert source rate
            default => $toRate / $fromRate                                // XXX→YYY: convert through base
        };
    }

    /**
     * Load exchange rates from API.
     * Provide rates (mainly for testing purposes).
     */
    public function loadRates(?array $rates = null): void
    {
        try {
            if (!empty($rates)) {
                $this->rates = $rates;
            } else {
                $ratesData = $this->fetchRatesFromApi();
                $this->rates = $ratesData['rates'] ?? [];
            }
            $this->rates[$this->baseCurrency] = 1.0; // Base currency rate is always 1
        } catch (\Exception $e) {
            // Fallback to hardcoded rates from the task description in case API is unavailable
            $this->rates = [
                'EUR' => 1.0,
                'USD' => 1.1497,
                'JPY' => 129.53,
            ];
        }
    }

    /**
     * Fetch rates from API.
     */
    private function fetchRatesFromApi(): array
    {
        // TODO: Uncomment the following line accordingly to use the real API data
        // $response = @file_get_contents($this->getApiUrl(). '/tasks/api/currency-exchange-rates?access_key='.$this->getApiKey());
        // Paid version of API:
        // $response = @file_get_contents('https://api.exchangeratesapi.io/latest?access_key='.$this->getApiKey());
        // Local testing
        $response = @file_get_contents('./src/Service/CurrencyExchange/test_data.json');

        if (false === $response) {
            throw new \RuntimeException('Unable to fetch exchange rates from API');
        }

        $response = json_decode($response, true) ?? [];

        return $response['success'] ? $response : [];
    }

    /**
     * Get an API key from environment variables.
     */
    private function getApiKey(): string
    {
        $apiKey = $this->envLoader->get('EXCHANGE_API_KEY');

        if (empty($apiKey)) {
            throw new \RuntimeException('Exchange API key not found in environment variables');
        }

        return $apiKey;
    }

    private function getApiUrl(): string
    {
        $apiUrl = $this->envLoader->get('EXCHANGE_API_URL');

        if (empty($apiUrl)) {
            throw new \RuntimeException('Exchange API URL not found in environment variables');
        }

        return rtrim($apiUrl, '/');
    }
}
