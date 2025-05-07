<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Uchman\CommissionTask\Entity\Transaction;
use Uchman\CommissionTask\Service\Commission\CommissionCalculator;
use Uchman\CommissionTask\Service\Commission\Operation\DepositStrategy;
use Uchman\CommissionTask\Service\Commission\Operation\WithdrawStrategy;
use Uchman\CommissionTask\Service\Commission\User\BusinessUserDepositStrategy;
use Uchman\CommissionTask\Service\Commission\User\BusinessUserWithdrawStrategy;
use Uchman\CommissionTask\Service\Commission\User\PrivateUserDepositStrategy;
use Uchman\CommissionTask\Service\Commission\User\PrivateUserWithdrawStrategy;
use Uchman\CommissionTask\Service\CurrencyExchange\ExchangeRateService;
use Uchman\CommissionTask\Service\Math;
use Uchman\CommissionTask\Service\TransactionProcessor;

class MainTest extends TestCase
{
    private Math $math;
    private ExchangeRateService $exchangeRateService;
    private CommissionCalculator $commissionCalculator;


	protected function setUp(): void
	{
		$this->initializeCalculator();
	}

	private function initializeCalculator(int $scale = 2): void
	{
		$this->math = new Math($scale);

		ExchangeRateService::resetInstance();
		$this->exchangeRateService = ExchangeRateService::getInstance($this->math);
		$this->exchangeRateService->loadRates([
			'EUR' => 1.0,
			'USD' => 1.1497,
			'JPY' => 129.53,
		]);

		$privateWithdraw = new PrivateUserWithdrawStrategy($this->math, $this->exchangeRateService);
		$businessWithdraw = new BusinessUserWithdrawStrategy($this->math);
		$privateDeposit = new PrivateUserDepositStrategy($this->math);
		$businessDeposit = new BusinessUserDepositStrategy($this->math);

		$withdrawOp = new WithdrawStrategy($this->math, [
			'private' => $privateWithdraw,
			'business' => $businessWithdraw,
		]);

		$depositOp = new DepositStrategy($this->math, [
			'private' => $privateDeposit,
			'business' => $businessDeposit,
		]);

		$this->commissionCalculator = new CommissionCalculator([
			$withdrawOp,
			$depositOp,
		]);
	}
    
    /**
     * Test the full processing of the sample data from the task description
     */
    public function testProcessSampleFile(): void
    {
	    // Reinitialize with scale 10 for this test
	    $this->initializeCalculator(scale: 10);

        // Create a temporary CSV file with test data
        $csvData = <<<CSV
					2014-12-31,4,private,withdraw,1200.00,EUR
					2015-01-01,4,private,withdraw,1000.00,EUR
					2016-01-05,4,private,withdraw,1000.00,EUR
					2016-01-05,1,private,deposit,200.00,EUR
					2016-01-06,2,business,withdraw,300.00,EUR
					2016-01-06,1,private,withdraw,30000,JPY
					2016-01-07,1,private,withdraw,1000.00,EUR
					2016-01-07,1,private,withdraw,100.00,USD
					2016-01-10,1,private,withdraw,100.00,EUR
					2016-01-10,2,business,deposit,10000.00,EUR
					2016-01-10,3,private,withdraw,1000.00,EUR
					2016-02-15,1,private,withdraw,300.00,EUR
					2016-02-19,5,private,withdraw,3000000,JPY
		CSV;
        $tempFile = tempnam(sys_get_temp_dir(), 'commission_test');
        file_put_contents($tempFile, $csvData);
        
        // Expected results from the task description
        $expectedResults = [
            "0.60",  // 2014-12-31, private withdraw 1200.00 EUR
            "3.00",  // 2015-01-01, private withdraw 1000.00 EUR
            "0.00",  // 2016-01-05, private withdraw 1000.00 EUR
            "0.06",  // 2016-01-05, private deposit 200.00 EUR
            "1.50",  // 2016-01-06, business withdraw 300.00 EUR
            "0",     // 2016-01-06, private withdraw 30000 JPY
            "0.70",  // 2016-01-07, private withdraw 1000.00 EUR
            "0.30",  // 2016-01-07, private withdraw 100.00 USD
            "0.30",  // 2016-01-10, private withdraw 100.00 EUR
            "3.00",  // 2016-01-10, business deposit 10000.00 EUR
            "0.00",  // 2016-01-10, private withdraw 1000.00 EUR
            "0.00",  // 2016-02-15, private withdraw 300.00 EUR
            "8612"   // 2016-02-19, private withdraw 3000000 JPY
        ];
        
        // Process the file
        $transactionProcessor = new TransactionProcessor($this->commissionCalculator);
        $actualResults = $transactionProcessor->processFile($tempFile);
        
        // Clean up
        unlink($tempFile);
        
        // Assert that the results match the expected values
        $this->assertEquals($expectedResults, $actualResults);
    }

    public function testPrivateDepositCommission(): void
    {
        $transaction = new Transaction(
            new \DateTime('2025-05-10'),
            1,
            'private',
            'deposit',
            '100.00',
            'EUR'
        );
        
        $commission = $this->commissionCalculator->calculate($transaction);
        $this->assertEquals('0.03', $commission);
    }
    
    public function testBusinessDepositCommission(): void
    {
        $transaction = new Transaction(
            new \DateTime('2025-05-10'),
            1,
            'business',
            'deposit',
            '100.00',
            'EUR'
        );
        
        $commission = $this->commissionCalculator->calculate($transaction);
        $this->assertEquals('0.03', $commission);
    }
    
    public function testBusinessWithdrawCommission(): void
    {
        $transaction = new Transaction(
            new \DateTime('2025-05-10'),
            1,
            'business',
            'withdraw',
            '100.00',
            'EUR'
        );
        
        $commission = $this->commissionCalculator->calculate($transaction);
        $this->assertEquals('0.50', $commission);
    }
    
    public function testPrivateWithdrawCommissionWithinFreeLimit(): void
    {
        $transaction = new Transaction(
            new \DateTime('2025-05-10'),
            1,
            'private',
            'withdraw',
            '500.00',
            'EUR'
        );
        
        $commission = $this->commissionCalculator->calculate($transaction);
        $this->assertEquals('0.00', $commission);
    }
    
    public function testPrivateWithdrawCommissionBeyondFreeLimit(): void
    {
        $transaction1 = new Transaction(
            new \DateTime('2025-05-10'),
            1,
            'private',
            'withdraw',
            '800.00',
            'EUR'
        );
        
        $transaction2 = new Transaction(
            new \DateTime('2025-05-10'),
            1,
            'private',
            'withdraw',
            '300.00',
            'EUR'
        );
        
        // First withdrawal (within limit)
        $commission1 = $this->commissionCalculator->calculate($transaction1);
        $this->assertEquals('0.00', $commission1);
        
        // Second withdrawal (partially beyond limit)
        $commission2 = $this->commissionCalculator->calculate($transaction2);
        $this->assertEquals('0.30', $commission2); // Commission on 100 EUR (300.00 - (1000 - 800))
    }

	// ------------------------------------
	// ------------ Math Tests ------------
	// ------------------------------------

	// Addition tests
	public function testAddTwoNaturalNumbers(): void
	{
		$result = $this->math->add('1', '2');
		$this->assertEquals('3.00', $result);
	}

	public function testAddNegativeToPositive(): void
	{
		$result = $this->math->add('-1', '2');
		$this->assertEquals('1.00', $result);
	}

	public function testAddNaturalNumberToFloat(): void
	{
		$result = $this->math->add('1', '1.05123');
		$this->assertEquals('2.05', $result);
	}

	public function testAddTwoLargeNumbers(): void
	{
		$result = $this->math->add('99999999.99', '0.01');
		$this->assertEquals('100000000.00', $result);
	}

	public function testAddTwoZeroValues(): void
	{
		$result = $this->math->add('0', '0');
		$this->assertEquals('0.00', $result);
	}

	// Subtraction tests
	public function testSubtractNaturalFromNatural(): void
	{
		$result = $this->math->subtract('3', '2');
		$this->assertEquals('1.00', $result);
	}

	public function testSubtractToGetNegativeResult(): void
	{
		$result = $this->math->subtract('1', '2');
		$this->assertEquals('-1.00', $result);
	}

	public function testSubtractFloatFromNatural(): void
	{
		$result = $this->math->subtract('5', '1.5');
		$this->assertEquals('3.50', $result);
	}

	public function testSubtractWithPrecisionLoss(): void
	{
		$result = $this->math->subtract('10', '0.123456');
		$this->assertEquals('9.87', $result);
	}

	public function testSubtractFromZero(): void
	{
		$result = $this->math->subtract('0', '5');
		$this->assertEquals('-5.00', $result);
	}

	public function testSubtractZero(): void
	{
		$result = $this->math->subtract('5', '0');
		$this->assertEquals('5.00', $result);
	}

	// Multiplication tests
	public function testMultiplyTwoNaturalNumbers(): void
	{
		$result = $this->math->multiply('2', '3');
		$this->assertEquals('6.00', $result);
	}

	public function testMultiplyByZero(): void
	{
		$result = $this->math->multiply('5', '0');
		$this->assertEquals('0.00', $result);
	}

	public function testMultiplyNegativeNumber(): void
	{
		$result = $this->math->multiply('-2', '3');
		$this->assertEquals('-6.00', $result);
	}

	public function testMultiplyTwoNegativeNumbers(): void
	{
		$result = $this->math->multiply('-2', '-3');
		$this->assertEquals('6.00', $result);
	}

	public function testMultiplyByDecimalLessThanOne(): void
	{
		$result = $this->math->multiply('10', '0.5');
		$this->assertEquals('5.00', $result);
	}

	public function testMultiplyPercentageRate(): void
	{
		$result = $this->math->multiply('100', '0.003');
		$this->assertEquals('0.30', $result);
	}

	public function testMultiplyWithPrecision(): void
	{
		$result = $this->math->multiply('2.5', '2.5');
		$this->assertEquals('6.25', $result);
	}

	// Division tests
	public function testDivideNaturalByNatural(): void
	{
		$result = $this->math->divide('6', '3');
		$this->assertEquals('2.00', $result);
	}

	public function testDivideToGetDecimalResult(): void
	{
		$result = $this->math->divide('5', '2');
		$this->assertEquals('2.50', $result);
	}

	public function testDivideByDecimal(): void
	{
		$result = $this->math->divide('10', '2.5');
		$this->assertEquals('4.00', $result);
	}

	public function testDivideZero(): void
	{
		$result = $this->math->divide('0', '5');
		$this->assertEquals('0.00', $result);
	}

	public function testDivideWithRecurringDecimal(): void
	{
		$result = $this->math->divide('10', '3');
		$this->assertEquals('3.33', $result);
	}

	public function testDivideNegativeNumber(): void
	{
		$result = $this->math->divide('-10', '2');
		$this->assertEquals('-5.00', $result);
	}

	public function testDivideByZero(): void
	{
		$this->expectException(\DivisionByZeroError::class);
		$this->math->divide('10', '0');
	}

	// Comparison tests
	public function testCompareEqualValues(): void
	{
		$result = $this->math->compare('5', '5');
		$this->assertEquals(0, $result);
	}

	public function testCompareLeftGreaterThanRight(): void
	{
		$result = $this->math->compare('10', '5');
		$this->assertEquals(1, $result);
	}

	public function testCompareLeftLessThanRight(): void
	{
		$result = $this->math->compare('5', '10');
		$this->assertEquals(-1, $result);
	}

	public function testCompareDecimalEqual(): void
	{
		$result = $this->math->compare('5.00', '5');
		$this->assertEquals(0, $result);
	}

	public function testCompareDecimalGreater(): void
	{
		$result = $this->math->compare('5.01', '5');
		$this->assertEquals(1, $result);
	}

	public function testCompareDecimalLess(): void
	{
		$result = $this->math->compare('4.99', '5');
		$this->assertEquals(-1, $result);
	}

	public function testCompareWithNegative(): void
	{
		$result = $this->math->compare('-5', '5');
		$this->assertEquals(-1, $result);
	}

	public function testCompareTwoNegatives(): void
	{
		$result = $this->math->compare('-5', '-10');
		$this->assertEquals(1, $result);
	}

	// Ceiling tests
	public function testCeilingToInteger(): void
	{
		$result = $this->math->ceiling('5.1', 0);
		$this->assertEquals('6', $result);
	}

	public function testCeilingToTwoDecimalsNoChange(): void
	{
		$result = $this->math->ceiling('5.10', 2);
		$this->assertEquals('5.10', $result);
	}

	public function testCeilingToTwoDecimalsRoundUp(): void
	{
		$result = $this->math->ceiling('5.001', 2);
		$this->assertEquals('5.01', $result);

		$result = $this->math->ceiling('0.69', 1);
		$this->assertEquals('0.70', $result);
	}

	public function testCeilingNegativeNumber(): void
	{
		$result = $this->math->ceiling('-5.1', 0);
		$this->assertEquals('-5', $result);
	}

	public function testCeilingJpyExample(): void
	{
		$result = $this->math->ceiling('8611.5', 0);
		$this->assertEquals('8612', $result);
	}

	public function testCeilingAlreadyRounded(): void
	{
		$result = $this->math->ceiling('5.00', 2);
		$this->assertEquals('5.00', $result);
	}

	public function testCeilingExactInteger(): void
	{
		$result = $this->math->ceiling('5', 0);
		$this->assertEquals('5', $result);
	}

	public function testCeilingWithRoundingToOneDecimals(): void
	{
		$result = $this->math->ceiling('0.06', 2);
		$this->assertEquals('0.06', $result);
	}


	// Scale tests
	public function testGetScaleWithFive(): void
	{
		$math = new Math(5);
		$this->assertEquals(5, $math->getScale());
	}

	public function testGetScaleWithTen(): void
	{
		$math = new Math(10);
		$this->assertEquals(10, $math->getScale());
	}
}
