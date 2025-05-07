<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service;

class Math
{
    private int $scale;

    public function __construct(int $scale)
    {
        $this->scale = $scale;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function add(string $leftOperand, string $rightOperand): string
    {
        return bcadd($leftOperand, $rightOperand, $this->scale);
    }

    public function subtract(string $leftOperand, string $rightOperand): string
    {
        return bcsub($leftOperand, $rightOperand, $this->scale);
    }

    public function multiply(string $leftOperand, string $rightOperand): string
    {
        return bcmul($leftOperand, $rightOperand, $this->scale);
    }

    public function divide(string $leftOperand, string $rightOperand): string
    {
        if ('0' === $rightOperand) {
            throw new \DivisionByZeroError('Division by zero');
        }

        return bcdiv($leftOperand, $rightOperand, $this->scale);
    }

    public function compare(string $leftOperand, string $rightOperand): int
    {
        return bccomp($leftOperand, $rightOperand, $this->scale);
    }

    public function ceiling(float|string $number, int $precision = 0): string
    {
        $number = (string) $number;

        if ($precision < 0) {
            throw new \RuntimeException('Invalid precision');
        }

        if (empty($precision)) {
            return (string) ceil((float) $number);
        }

        bcscale($this->scale);

        // Calculate multiplier for the required precision
        $multiplier = bcpow('10', (string) $precision, $this->scale);

        // Scale the number - multiply by 10^precision
        $scaled = bcmul($number, $multiplier, $this->scale);

        // Apply ceiling operation
        $ceil = (string) ceil((float) $scaled);

        // Scale back to original precision
        $result = (float) bcdiv($ceil, $multiplier, $this->scale);

        // Format to ensure the correct number of decimal places including trailing zeros
        return number_format($result, 2, '.', '');
    }
}
