<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;
use tests\Kohana\Support\ObjectInternalAccessTestWorkarounds;
use tests\Kohana\Support\KohanaCoreTestCase;

defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Tests the session class
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
#[Group('kohana.core.session')]
class Kohana_SessionTest extends KohanaCoreTestCase
{
	use ObjectInternalAccessTestWorkarounds;

    public function getTestableSessionDriver(?array $config = NULL, $id = NULL, SessionDriverMethodMock $driverMock = new SessionDriverMethodMock()): Session
	{
        // This is fairly ugly, but it reflects the original Kohana design where drivers extend `Session` rather than
        // being injected into them through e.g. a driver interface.
        // PHPUnit no longer cleanly supports mocking an abstract class, so we separate the underlying Session
        // implementation from the mockable / injectable abstract method implementations for testing.
        return new class($driverMock, $config, $id) extends Session {
            public function __construct(
                public readonly SessionDriverMethodMock $mock,
                ?array $config = NULL,
                $id = NULL
            )
            {
                parent::__construct($config, $id);
            }

            protected function _read($id = NULL)
            {
                return $this->mock->doCall(__FUNCTION__, func_get_args());
            }

            protected function _regenerate()
            {
                return $this->mock->doCall(__FUNCTION__, func_get_args());
            }

            protected function _write()
            {
                return $this->mock->doCall(__FUNCTION__, func_get_args());
            }

            protected function _destroy()
            {
                return $this->mock->doCall(__FUNCTION__, func_get_args());
            }

            protected function _restart()
            {
                return $this->mock->doCall(__FUNCTION__, func_get_args());
            }
        };
	}

	/**
	 * Provides test data for
	 *
	 * test_constructor_uses_name_from_config_and_casts()
	 *
	 * @return array
	 */
	public static function provider_constructor_uses_settings_from_config_and_casts()
	{
		return [
			// array(expected, input)
			// data set 0
			[
				[
					'name'     => 'awesomeness',
					'lifetime' => 1231456421,
				],
				[
					'name'     => 'awesomeness',
					'lifetime' => '1231456421',
				],
			],
			// data set 1
			[
				[
					'name' => '123',
				],
				[
					'name' => 123,
				],
			],
		];
	}

	/**
     * The constructor should change its attributes based on config
     * passed as the first parameter
     */
    #[DataProvider('provider_constructor_uses_settings_from_config_and_casts')]
    #[Test]
    public function test_constructor_uses_settings_from_config_and_casts($expected, $config)
	{
		$session = $this->getTestableSessionDriver($config);

		foreach ($expected as $var => $value) {
			$this->assertAttributeSame($value, '_'.$var, $session);
		}
	}

	/**
     * Check that the constructor will load a session if it's provided
     * witha session id
     */
    #[Test]
    public function test_constructor_loads_session_with_session_id()
	{
        $session_id = 'lolums';
        $mock = new SessionDriverMethodMock();

        // Create the session
        $session = $this->getTestableSessionDriver([], $session_id, $mock);

        $this->assertSame(
            [
                ['_read'=> [$session_id]],
            ],
            $mock->getCalls(),
        );
	}

	/**
     * Calling $session->bind() should allow you to bind a variable
     * to a session variable
     */
    #[Ticket('3164')]
    #[Test]
    public function test_bind_actually_binds_variable()
	{
		$session = $this->getTestableSessionDriver();

		$var = 'asd';

		$session->bind('our_var', $var);

		$var = 'foobar';

		$this->assertSame('foobar', $session->get('our_var'));
	}


	/**
     * When a session is initially created it should have no data
     *
     */
    #[Test]
    public function test_initially_session_has_no_data()
	{
		$session = $this->getTestableSessionDriver();

		$this->assertAttributeSame([], '_data', $session);
	}

	/**
     * Make sure that the default session name (the one used if the
     * driver does not set one) is 'session'
     */
    #[Test]
    public function test_default_session_name_is_set()
	{
		$session = $this->getTestableSessionDriver();

		$this->assertAttributeSame('session', '_name', $session);
	}

	/**
     * A new session should not be classed as destroyed
     */
    #[Test]
    public function test_default_session_is_not_classed_as_destroyed()
	{
		$session = $this->getTestableSessionDriver();

		$this->assertAttributeSame(FALSE, '_destroyed', $session);
	}

	/**
	 * Provides test data for test_get_returns_default_if_var_dnx()
	 *
	 * @return array
	 */
	public static function provider_get_returns_default_if_var_dnx()
	{
		return [
			['something_crazy', FALSE],
			['a_true', TRUE],
			['an_int', 158163158],
		];
	}

	/**
     * Make sure that get() is using the default value we provide and
     * isn't tampering with it
     */
    #[DataProvider('provider_get_returns_default_if_var_dnx')]
    #[Test]
    public function test_get_returns_default_if_var_dnx($var, $default)
	{
		$session = $this->getTestableSessionDriver();

		$this->assertSame($default, $session->get($var, $default));
	}

	/**
     * By default get() should be using null as the var DNX return value
     */
    #[Test]
    public function test_get_uses_null_as_default_return_value()
	{
		$session = $this->getTestableSessionDriver();

		$this->assertSame(NULL, $session->get('level_of_cool'));
	}

	/**
     * This test makes sure that session is using array_key_exists
     * as isset will return FALSE if the value is NULL
     */
    #[Test]
    public function test_get_returns_value_if_it_equals_null()
	{
		$session = $this->getTestableSessionDriver();

		$session->set('arkward', NULL);

		$this->assertSame(NULL, $session->get('arkward', 'uh oh'));
	}

	/**
     * as_array() should return the session data by reference.
     *
     * i.e. if we modify the returned data, the session data also changes
     */
    #[Test]
    public function test_as_array_returns_data_by_ref_or_copy()
	{
		$session = $this->getTestableSessionDriver();

		$data_ref =& $session->as_array();

		$data_ref['something'] = 'pie';

		$this->assertAttributeSame($data_ref, '_data', $session);

		$data_copy = $session->as_array();

		$data_copy['pie'] = 'awesome';

		$this->assertNotSame($data_copy, $this->readAttribute($session, '_data'));
	}

	/**
     * set() should add new session data and modify existing ones
     *
     * Also makes sure that set() returns $this
     */
    #[Test]
    public function test_set_adds_and_modifies_to_session_data()
	{
		$session = $this->getTestableSessionDriver();

		$this->assertSame($session, $session->set('pork', 'pie'));

		$this->assertAttributeSame(
			['pork' => 'pie'],
			'_data',
			$session,
		);

		$session->set('pork', 'delicious');

		$this->assertAttributeSame(
			['pork' => 'delicious'],
			'_data',
			$session,
		);
	}

	/**
     * This tests that delete() removes specified session data
     */
    #[Test]
    public function test_delete_removes_select_session_data()
	{
		$session = $this->getTestableSessionDriver();

		// Bit of a hack for mass-loading session data
		$data =& $session->as_array();

		$data += [
			'a'    => 'A',
			'b'    => 'B',
			'c'    => 'C',
			'easy' => '123',
		];

		// Make a copy of $data for testing purposes
		$copy = $data;

		// First we make sure we can delete one item
		// Also, check that delete returns $this
		$this->assertSame($session, $session->delete('a'));

		unset($copy['a']);

		// We could test against $data but then we'd be testing
		// that as_array() is returning by ref
		$this->assertAttributeSame($copy, '_data', $session);

		// Now we make sure we can delete multiple items
		// We're checking $this is returned just in case
		$this->assertSame($session, $session->delete('b', 'c'));
		unset($copy['b'], $copy['c']);

		$this->assertAttributeSame($copy, '_data', $session);
	}

	/**
	 * Provides test data for test_read_loads_session_data()
	 *
	 * @return array
	 */
	public static function provider_read_loads_session_data()
	{
		return [
			// If driver returns array then just load it up
			[
				[],
				'wacka_wacka',
				[],
			],
			[
				['the it' => 'crowd'],
				'the_it_crowd',
				['the it' => 'crowd'],
			],
			// If it's a string an encrpytion is disabled (by default) base64decode and unserialize
			[
				['dead' => 'arrival'],
				'lolums',
				'YToxOntzOjQ6ImRlYWQiO3M6NzoiYXJyaXZhbCI7fQ==',
			],
		];
	}

	/**
     * This is one of the "big" tests for the session lib
     *
     * The test makes sure that
     *
     * 1. Session asks the driver for the data relating to $session_id
     * 2. That it will load the returned data into the session
     */
    #[DataProvider('provider_read_loads_session_data')]
    #[Test]
    public function test_read_loads_session_data($expected_data, $session_id, $driver_data, array $config = [])
	{
        $mock = new SessionDriverMethodMock(
            _read: fn($i) => $driver_data,
        );
		$session = $this->getTestableSessionDriver(config: $config, driverMock: $mock);

		$session->read($session_id);
		$this->assertAttributeSame($expected_data, '_data', $session);
        $this->assertSame(
            [
                ['_read' => [null]],
                ['_read' => [$session_id]],
            ],
            $mock->getCalls(),
        );
	}

	/**
     * regenerate() should tell the driver to regenerate its id
     */
    #[Test]
    public function test_regenerate_tells_driver_to_regenerate()
	{
        $new_session_id = 'asdnoawdnoainf';

        $mock = new SessionDriverMethodMock(
            _regenerate: fn() => $new_session_id,
        );
        $session = $this->getTestableSessionDriver(driverMock: $mock);


		$this->assertSame($new_session_id, $session->regenerate());
        $this->assertSame(
            [
                ['_read' => [null]],
                ['_regenerate' => []],
            ],
            $mock->getCalls(),
        );
	}

	/**
     * If the driver destroys the session then all session data should be
     * removed
     */
    #[Test]
    public function test_destroy_deletes_data_if_driver_destroys_session()
	{
        $mock = new SessionDriverMethodMock(
            _destroy: fn() => TRUE,
        );
		$session = $this->getTestableSessionDriver(driverMock: $mock);

		$session
			->set('asd', 'dsa')
			->set('dog', 'god');

		$this->assertTrue($session->destroy());
        $this->assertSame([
            ['_read' => [null]],
            ['_destroy' => []],
        ], $mock->getCalls());

		$this->assertAttributeSame([], '_data', $session);
	}

	/**
     * The session data should only be deleted if the driver reports
     * that the session was destroyed ok
     */
    #[Test]
    public function test_destroy_only_deletes_data_if_driver_destroys_session()
	{
        $mock = new SessionDriverMethodMock(
            _destroy: fn() => FALSE,
        );
		$session = $this->getTestableSessionDriver(driverMock: $mock);

		$session
			->set('asd', 'dsa')
			->set('dog', 'god');

		$this->assertFalse($session->destroy());
        $this->assertSame([
            ['_read' => [null]],
            ['_destroy' => []],
        ], $mock->getCalls());

		$this->assertAttributeSame(
			['asd' => 'dsa', 'dog' => 'god'],
			'_data',
			$session,
		);
	}

	/**
     * If a session variable exists then get_once should get it then remove it.
     * If the variable does not exist then it should return the default
     */
    #[Test]
    public function test_get_once_gets_once_or_returns_default()
	{
		$session = $this->getTestableSessionDriver();

		$session->set('foo', 'bar');

		// Test that a default is returned
		$this->assertSame('mud', $session->get_once('fud', 'mud'));

		// Now test that it actually removes the value
		$this->assertSame('bar', $session->get_once('foo'));

		$this->assertAttributeSame([], '_data', $session);

		$this->assertSame('maybe', $session->get_once('foo', 'maybe'));
	}
}

final class SessionDriverMethodMock {
    private array $driverCalls = [];

    public function __construct(
        private readonly ?Closure $_read = null,
        private readonly ?Closure $_regenerate = null,
        private readonly ?Closure $_write = null,
        private readonly ?Closure $_destroy = null,
        private readonly ?Closure $_restart = null,
    )
    {
    }

    public function getCalls():array
    {
        return $this->driverCalls;
    }

    public function doCall(string $method, array $args):mixed
    {
        $this->driverCalls[] = [$method => $args];
        if ($this->$method !== null) {
            return ($this->$method)(...$args);
        }

        return null;
    }

}
