<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use tests\Kohana\Support\KohanaCoreTestCase;

/**
 * Test case for Minion_Util
 *
 * @package    Kohana/Minion
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
#[Group('kohana')]
#[Group('kohana.minion')]
class Minion_TaskTest extends KohanaCoreTestCase
{
	/**
	 * Provides test data for test_convert_task_to_class_name()
	 *
	 * @return array
	 */
	public static function provider_convert_task_to_class_name()
	{
		return array(
			array('Task_Db_Migrate', 'db:migrate'),
			array('Task_Db_Status',  'db:status'),
			array('Task_Db_DoComplexThings', 'db:do-complex-things'),
			array('', ''),
		);
	}

	/**
     * Tests that a task can be converted to a class name
     *
     * @param string Expected class name
     * @param string Input task name
     */
    #[DataProvider('provider_convert_task_to_class_name')]
    #[Test]
    public function test_convert_task_to_class_name($expected, $task_name)
	{
		$this->assertSame($expected, Minion_Task::convert_task_to_class_name($task_name));
	}

	/**
	 * Provides test data for test_convert_class_to_task()
	 *
	 * @return array
	 */
	public static function provider_convert_class_to_task()
	{
		return array(
			array('db:migrate', 'Task_Db_Migrate'),
            array('db:do-complex-things', 'Task_Db_DoComplexThings'),
		);
	}

	/**
     * Tests that the task name can be found from a class name / object
     *
     * @param string Expected task name
     * @param mixed  Input class
     */
    #[DataProvider('provider_convert_class_to_task')]
    #[Test]
    public function test_convert_class_to_task($expected, $class)
	{
		$this->assertSame($expected, Minion_Task::convert_class_to_task($class));
	}
}
