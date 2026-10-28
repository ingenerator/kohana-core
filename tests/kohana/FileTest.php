<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use tests\Kohana\Support\KohanaCoreTestCase;

defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Tests Kohana File helper
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
#[Group('kohana.core.file')]
class Kohana_FileTest extends KohanaCoreTestCase
{
	/**
	 * Provides test data for test_sanitize()
	 *
	 * @return array
	 */
	public static function provider_mime()
	{
		return [
			// $value, $result
			[Kohana::find_file('tests', 'test_data/github', 'png'), 'image/png'],
		];
	}

	/**
     * Tests File::mime()
     *
     *
     * @param boolean $input    Input for File::mime
     * @param boolean $expected Output for File::mime
     */
    #[DataProvider('provider_mime')]
    #[Test]
    public function test_mime($input, $expected)
	{
		//@todo: File::mime coverage needs significant improvement or to be dropped for a composer package - it's a "horribly unreliable" method with very little testing
		$this->assertSame($expected, File::mime($input));
	}

	/**
	 * Provides test data for test_split_join()
	 *
	 * @return array
	 */
	public static function provider_split_join()
	{
		return [
			// $value, $result
			[Kohana::find_file('tests', 'test_data/github', 'png'), .01, 1],
		];
	}

	/**
     * Tests File::mime()
     *
     *
     * @param boolean $input    Input for File::split
     * @param boolean $peices   Input for File::split
     * @param boolean $expected Output for File::splut
     */
    #[DataProvider('provider_split_join')]
    #[Test]
    public function test_split_join($input, $peices, $expected)
	{
		$this->assertSame($expected, File::split($input, $peices));
		$this->assertSame($expected, File::join($input));

		foreach (glob(Kohana::find_file('tests', 'test_data/github', 'png').'.*') as $file) {
			unlink($file);
		}
	}
}
