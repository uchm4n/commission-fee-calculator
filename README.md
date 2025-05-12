# Commission Fee Calculator

This application processes CSV files of financial operations and calculates commission fees according to rules for private and business clients. 
It uses the Strategy pattern to handle different user and operation types, 
a Singleton service for exchange rates, 
and follows **SOLID** principles for maintainability and extensibility.

## Features

- Deposit fee: 0.03 % of the amount
- Business withdraw fee: 0.5 % of the amount
- Private withdraw fee: 0.3 % of the amount with 1000 EUR per week (Mon–Sun) free for the first 3 operations
- Automatic currency conversion via a Singleton `ExchangeRateService`
- Precise arithmetic with `Math` service (BCMath)
- PSR-4 autoloading and PSR-12 code style
- PHPUnit tests covering all core behaviors

## Rrquirements

- PHP 8.3
- Composer
- BC Math Extension


## Installation

1. Clone the repository
   ```bash
   git clone https://github.com/uchm4n/commission-fee-calculator.git
   cd commission-fee-calculator
   ```
2. Install PHP dependencies
   ```bash
   composer install
   ```
3. Copy environment template
   ```bash
   cp .env.example .env
   ```
4. (Optional) Set `EXCHANGE_API_KEY` and `EXCHANGE_API_URL` in `.env`

## Usage

Run the script with a CSV file as input:

```bash
php run.php input.csv
```

Output is printed line by line, one commission fee per operation.

## Testing

Execute the full test suite with PHPUnit:

```bash
composer test
```

Or directly:

```bash
vendor/bin/phpunit --configuration phpunit.xml
```

## Architecture

### Strategy Pattern
- `CommissionStrategyFactory` builds a `CommissionCalculator` with:
    - `WithdrawStrategy` (delegates to `PrivateUserWithdrawStrategy` or `BusinessUserWithdrawStrategy`)
    - `DepositStrategy` (delegates to `PrivateUserDepositStrategy` or `BusinessUserDepositStrategy`)

### Singleton Service
- `ExchangeRateService::getInstance()` ensures a single source of truth for currency rates and can be reset for testing.

## Configuration

- `Math` scale (internal precision) is set in `run.php` when constructing `new Math(10)`
- Currency precisions are defined in `AbstractUserStrategy::getCurrencyPrecision()`

## Project Structure

```
src/
├── Entity/
│   └── Transaction.php
├── Service/
│   ├── Math.php
│   ├── CurrencyExchange/
│   │   └── ExchangeRateService.php
│   └── Commission/
│       ├── User/
│       │   ├── PrivateUserWithdrawStrategy.php
│       │   └── BusinessUserDepositStrategy.php
│       └── Operation/
│           ├── WithdrawStrategy.php
│           └── DepositStrategy.php
run.php
tests/
└── Service/
    └── MainTest.php
composer.json
README.md
```

---

All parts of the system (strategy registration, rate loading, arithmetic) are decoupled and configurable, making it easy to extend for new currencies, user types, or commission rules.