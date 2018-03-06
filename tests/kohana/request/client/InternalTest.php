<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * Unit tests for internal request client
 *
 * @group kohana
 * @group kohana.core
 * @group kohana.core.request
 * @group kohana.core.request.client
 * @group kohana.core.request.client.internal
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Request_Client_InternalTest extends Unittest_TestCase
{

	protected $_log_object;

	// @codingStandardsIgnoreStart
	public function setUp()
	// @codingStandardsIgnoreEnd
	{
		parent::setUp();

		// temporarily save $log object
		$this->_log_object = Kohana::$log;
		Kohana::$log = NULL;
	}

	// @codingStandardsIgnoreStart
	public function tearDown()
	// @codingStandardsIgnoreEnd
	{
		// re-assign log object
		Kohana::$log = $this->_log_object;

		parent::tearDown();
	}

	public function provider_response_failure_status()
	{
		return array(
			array('', 'Welcome', 'missing_action', 'Welcome/missing_action', 404),
			array('kohana3', 'missing_controller', 'index', 'kohana3/missing_controller/index', 404),
			array('', 'Template', 'missing_action', 'kohana3/Template/missing_action', 500),
		);
	}

	/**
	 * Tests for correct exception messages
	 *
	 * @test
	 * @dataProvider provider_response_failure_status
	 *
	 * @return null
	 */
	public function test_response_failure_status($directory, $controller, $action, $uri, $expected)
	{
		$request = \Request::with(
			[
				'directory'  => $directory,
				'controller' => $controller,
				'action'     => $action,
				'uri'        => $uri,
				'method'     => \Request::GET,
			]
		);

		$internal_client = new Request_Client_Internal;

		$response = $internal_client->execute($request);

		$this->assertSame($expected, $response->status());
	}

	public function provider_controller_class()
	{
		return [
			[
				['directory' => NULL, 'controller' => 'Test', 'action' => 'anything'],
				'Controller_Test',
				'Controller_Test::anything'
			],
			[
				['directory' => 'Subdir', 'controller' => 'Test', 'action' => 'anything'],
				'Controller_Subdir_Test',
				'Controller_Subdir_Test::anything'
			],
			[
				['directory' => NULL, 'controller' => '\My\Test\Test', 'action' => 'things'],
				'\My\Test\Test',
				'My\Test\Test::things'
			],
			[
				['directory' => NULL, 'controller' => '\My\Test\TestController', 'action' => 'things'],
				'\My\Test\TestController',
				'My\Test\TestController::things'
			],

		];
	}

	/**
	 * @dataProvider provider_controller_class
	 */
	public function test_it_executes_expected_controller_class($params, $controller_class, $expect)
	{
		if ( ! class_exists($controller_class)) {
			$this->defineExtensionClass($controller_class, ClassReturningController::class);
		}
		$client   = new Request_Client_Internal;
		$request  = \Request::with($params);
		$response = $client->execute($request);
		$this->assertEquals($expect, $response->body());
	}

	protected function defineExtensionClass($fqcn, $base_class)
	{
		$parts = array_filter(explode('\\', $fqcn));
		$class = array_pop($parts);
		$ns    = implode('\\', $parts);
		if ($ns) {
			$ns = 'namespace '.$ns.';';
		}
		eval("$ns class $class extends \\$base_class {}");
	}
}

class ClassReturningController extends Controller {

	public function execute()
	{
		$this->response->body(get_class($this).'::'.$this->request->action());
		return $this->response;
	}
}
