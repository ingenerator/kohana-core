<?php defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Tests Kohana Exception Class
 *
 * @group          kohana
 * @group          kohana.core
 * @group          kohana.core.exception
 *
 * @package        Kohana
 * @category       Tests
 * @author         Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license        http://kohanaframework.org/license
 */
class Kohana_ExceptionTest extends Unittest_TestCase
{
	/**
	 * Provides test data for test_constructor()
	 *
	 * @return array
	 */
	public function provider_constructor()
	{
		return [
			[[''], '', 0],
			[[':a'], ':a', 0],

			[[':a', NULL], ':a', 0],
			[[':a', []], ':a', 0],
			[[':a', [':a' => 'b']], 'b', 0],
			[[':a :b', [':a' => 'c', ':b' => 'd']], 'c d', 0],

			[[':a', NULL, 5], ':a', 5],
			// #3358
			[[':a', NULL, '3F000'], ':a', '3F000'],
			// #3404
			[[':a', NULL, '42S22'], ':a', '42S22'],
			// #3927
			[[':a', NULL, 'b'], ':a', 'b'],
			// #4039
			[[':a', NULL, '25P01'], ':a', '25P01'],
		];
	}

	/**
	 * Tests Kohana_Kohana_Exception::__construct()
	 *
	 * @test
	 * @dataProvider provider_constructor
	 * @covers       Kohana_Kohana_Exception::__construct
	 *
	 * @param array          $arguments        Arguments
	 * @param string         $expected_message Value from getMessage()
	 * @param integer|string $expected_code    Value from getCode()
	 */
	public function test_constructor($arguments, $expected_message, $expected_code)
	{
		switch (count($arguments)) {
			case 1:
				$exception = new Kohana_Exception(reset($arguments));
				break;
			case 2:
				$exception = new Kohana_Exception(reset($arguments), next($arguments));
				break;
			default:
				$exception = new Kohana_Exception(reset($arguments), next($arguments), next($arguments));
		}

		$this->assertSame($expected_code, $exception->getCode());
		$this->assertSame($expected_message, $exception->getMessage());
	}

	/**
	 * Provides test data for test_text()
	 *
	 * @return array
	 */
	public function provider_text()
	{
		$exception = new Kohana_Exception('foobar');
		$exception_line = __LINE__ - 1;
		return [
			[
				$exception,
				$this->dirSeparator(
					"Kohana_Exception [ 0 ]: foobar ~ SYSPATH/tests/kohana/ExceptionTest.php [ $exception_line ]"
				),
			],
		];
	}

	/**
	 * Tests Kohana_Exception::text()
	 *
	 * @test
	 * @dataProvider provider_text
	 * @covers       Kohana_Exception::text
	 *
	 * @param object $exception exception to test
	 * @param string $expected  expected output
	 */
	public function test_text($exception, $expected)
	{
		$this->assertEquals($expected, Kohana_Exception::text($exception));
	}
}
