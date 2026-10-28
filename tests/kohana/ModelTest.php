<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use tests\Kohana\Support\KohanaCoreTestCase;

defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * This test only really exists for code coverage.
 *
 *
 * @package        Kohana
 * @category       Tests
 * @author         Kohana Team
 * @author         BRMatt <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license        http://kohanaframework.org/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.model')]
class Kohana_ModelTest extends KohanaCoreTestCase
{
	/**
     * Test the model's factory.
     */
    #[Test]
    public function test_create()
	{
		$foobar = Model::factory('Foobar');

		$this->assertEquals(TRUE, $foobar instanceof Model);
	}
}

class Model_Foobar extends Model
{

}
