<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use tests\Kohana\Support\ObjectInternalAccessTestWorkarounds;
use tests\Kohana\Support\KohanaCoreTestCase;

defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Tests Kohana Logging API
 *
 *
 * @package        Kohana
 * @category       Tests
 * @author         Kohana Team
 * @author         Matt Button <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license        http://kohanaframework.org/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.logging')]
class Kohana_LogTest extends KohanaCoreTestCase
{
	use ObjectInternalAccessTestWorkarounds;

	/**
     * Tests that when a new logger is created the list of messages is initially
     * empty
     */
    #[Test]
    public function test_messages_is_initially_empty()
	{
		$logger = new Log;

		$this->assertAttributeSame([], '_messages', $logger);
	}

	/**
     * Tests that when a new logger is created the list of writers is initially
     * empty
     */
    #[Test]
    public function test_writers_is_initially_empty()
	{
		$logger = new Log;

		$this->assertAttributeSame([], '_writers', $logger);
	}

	/**
     * Test that attaching a log writer using an array of levels adds it to the array of log writers
     *
     * @TODO   Is this test too specific?
     */
    #[Test]
    public function test_attach_attaches_log_writer_and_returns_this()
	{
		$logger = new Log;
		$writer = new class extends Log_Writer {
            public function write(array $messages)
            {
            }
        };

		$this->assertSame($logger, $logger->attach($writer));

		$this->assertAttributeSame(
			[spl_object_hash($writer) => ['object' => $writer, 'levels' => []]],
			'_writers',
			$logger
		);
	}

	/**
     * Test that attaching a log writer using a min/max level adds it to the array of log writers
     *
     * @TODO   Is this test too specific?
     */
    #[Test]
    public function test_attach_attaches_log_writer_min_max_and_returns_this()
	{
		$logger = new Log;
        $writer = new class extends Log_Writer {
            public function write(array $messages)
            {
            }
        };

		$this->assertSame($logger, $logger->attach($writer, Log::NOTICE, Log::CRITICAL));

		$this->assertAttributeSame(
			[
				spl_object_hash($writer) => [
					'object' => $writer,
					'levels' => [Log::CRITICAL, Log::ERROR, Log::WARNING, Log::NOTICE],
				],
			],
			'_writers',
			$logger
		);
	}

	/**
     * When we call detach() we expect the specified log writer to be removed
     */
    #[Test]
    public function test_detach_removes_log_writer_and_returns_this()
	{
		$logger = new Log;
        $writer = new class extends Log_Writer {
            public function write(array $messages)
            {
            }
        };

		$logger->attach($writer);

		$this->assertSame($logger, $logger->detach($writer));

		$this->assertAttributeSame([], '_writers', $logger);
	}


}
