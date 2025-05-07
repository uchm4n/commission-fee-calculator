<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Uchman\CommissionTask\Service\Math;

class MathTest extends TestCase
{
	/**
	 * @var Math
	 */
	private $math;

	public function setUp(): void
	{
		$this->math = new Math(2);
	}

	/**
	 * @param string $leftOperand
	 * @param string $rightOperand
	 * @param string $expectation
	 *
	 * @dataProvider dataProviderForAddTesting
	 *
	 * // public function testAdd(string $leftOperand, string $rightOperand, string $expectation)
	 */
	public function testAdd()
	{
		// just testing tests
		$this->assertTrue(true);
	}

	public function dataProviderForAddTesting(): array
	{
		return [
			'add 2 natural numbers'             => ['1', '2', '3'],
			'add negative number to a positive' => ['-1', '2', '1'],
			'add natural number to a float'     => ['1', '1.05123', '2.05'],
		];
	}
}
