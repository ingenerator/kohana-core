<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use tests\Kohana\Support\KohanaCoreTestCase;

defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Tests Kohana upload class
 *
 *
 * @package        Kohana
 * @category       Tests
 * @author         Kohana Team
 * @author         Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license        http://kohanaframework.org/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.upload')]
class Kohana_UploadTest extends KohanaCoreTestCase
{
	/**
	 * Provides test data for test_size()
	 *
	 * @return array
	 */
	public static function provider_size()
	{
		return [
			// $field, $bytes, $environment, $expected
			[
				'unit_test',
				5,
				['_FILES' => ['unit_test' => ['error' => UPLOAD_ERR_INI_SIZE]]],
				FALSE,
			],
			[
				'unit_test',
				5,
				['_FILES' => ['unit_test' => ['error' => UPLOAD_ERR_NO_FILE]]],
				TRUE,
			],
			[
				'unit_test',
				'6K',
				[
					'_FILES' => [
						'unit_test' => [
							'error'    => UPLOAD_ERR_OK,
							'name'     => 'Unit_Test File',
							'type'     => 'image/png',
							'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
							'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
						],
					],
				],
				TRUE,
			],
			[
				'unit_test',
				'1B',
				[
					'_FILES' => [
						'unit_test' => [
							'error'    => UPLOAD_ERR_OK,
							'name'     => 'Unit_Test File',
							'type'     => 'image/png',
							'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
							'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
						],
					],
				],
				FALSE,
			],
		];
	}

	/**
     * Tests Upload::size
     *
     *
     * @param string $field       the files field to test
     * @param string $bytes       valid bite size
     * @param array  $environment set the $_FILES array
     * @param bool   $expected    what to expect
     */
    #[DataProvider('provider_size')]
    #[Test]
    public function test_size($field, $bytes, $environment, $expected)
	{
		$this->setEnvironment($environment);

		$this->assertSame($expected, Upload::size($_FILES[$field], $bytes));
	}

	/**
     * size() should throw an exception of the supplied max size is invalid
     */
    #[Test]
    public function test_size_throws_exception_for_invalid_size()
	{
		$this->setEnvironment([
			'_FILES' => [
				'unit_test' => [
					'error'    => UPLOAD_ERR_OK,
					'name'     => 'Unit_Test File',
					'type'     => 'image/png',
					'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
					'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
				],
			],
		]);

		$this->expectException(Kohana_Exception::class);
		Upload::size($_FILES['unit_test'], '1DooDah');
	}

	public static function provider_not_empty()
	{
		return [
			[
				NULL,
				FALSE,
			],
			[
				'',
				FALSE,
			],
			[
				[],
				FALSE,
			],
			[
				['error' => UPLOAD_ERR_CANT_WRITE, 'tmp_name' => uniqid(sys_get_temp_dir())],
				FALSE,
			],
			//            [
			//                // Can't test in reality for the passing case as it calls is_uploaded_file
			//                ['error' => UPLOAD_ERR_OK, 'tmp_name' => uniqid(sys_get_temp_dir())],
			//                TRUE
			//            ]
		];
	}

	#[DataProvider('provider_not_empty')]
    public function test_not_empty($to_validate, $expect)
	{
		$this->assertSame($expect, Upload::not_empty($to_validate));
	}

	/**
     * Provides test data for test_valid()
     */
    public static function provider_valid(): array
	{
		return [
			[
				TRUE,
				[
					'error'    => UPLOAD_ERR_OK,
					'name'     => 'Unit_Test File',
					'type'     => 'image/png',
					'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
					'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
				],
			],
			[
				FALSE,
				[
					'name'     => 'Unit_Test File',
					'type'     => 'image/png',
					'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
					'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
				],
			],
			[
				FALSE,
				[
					'error'    => UPLOAD_ERR_OK,
					'type'     => 'image/png',
					'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
					'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
				],
			],
			[
				FALSE,
				[
					'name'     => 'Unit_Test File',
					'error'    => UPLOAD_ERR_OK,
					'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
					'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
				],
			],
			[
				FALSE,
				[
					'error' => UPLOAD_ERR_OK,
					'name'  => 'Unit_Test File',
					'type'  => 'image/png',
					'size'  => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
				],
			],
			[
				FALSE,
				[
					'error'    => UPLOAD_ERR_OK,
					'name'     => 'Unit_Test File',
					'type'     => 'image/png',
					'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
				],
			],

		];
	}

	/**
     * Test Upload::valid
     */
    #[DataProvider('provider_valid')]
    #[Test]
    public function test_valid($expected, $file)
	{
		$this->setEnvironment([
			'_FILES' => [
				'unit_test' => $file,
			],
		]);

		$this->assertSame($expected, Upload::valid($_FILES['unit_test']));
	}

	/**
     * Tests Upload::type
     */
    #[Test]
    public function test_type()
	{
		$this->setEnvironment([
			'_FILES' => [
				'unit_test' => [
					'error'    => UPLOAD_ERR_OK,
					'name'     => 'github.png',
					'type'     => 'image/png',
					'tmp_name' => Kohana::find_file('tests', 'test_data/github', 'png'),
					'size'     => filesize(Kohana::find_file('tests', 'test_data/github', 'png')),
				],
			],
		]);

		$this->assertTrue(Upload::type($_FILES['unit_test'], ['jpg', 'png', 'gif']));

		$this->assertFalse(Upload::type($_FILES['unit_test'], ['docx']));
	}
}
