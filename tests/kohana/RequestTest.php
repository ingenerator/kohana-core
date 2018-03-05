<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * Unit tests for request class
 *
 * @group kohana
 * @group kohana.core
 * @group kohana.core.request
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_RequestTest extends Unittest_TestCase
{
	protected $_inital_request;

	// @codingStandardsIgnoreStart
	public function setUp()
	// @codingStandardsIgnoreEnd
	{
		parent::setUp();
		Kohana::$config->load('url')->set('trusted_hosts', array('localhost'));
		$this->_initial_request = Request::$initial;
		Request::$initial = Request::with(['uri' => '/']);
	}

	// @codingStandardsIgnoreStart
	public function tearDown()
	// @codingStandardsIgnoreEnd
	{
		Request::$initial = $this->_initial_request;
		parent::tearDown();
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function test_its_factory_just_throws()
	{
		\Request::factory();
	}

	public function test_from_globals_loads_from_global_state()
	{
		$this->setEnvironment(array(
			'Request::$initial' => NULL,
			'Request::$client_ip' => NULL,
			'Request::$user_agent' => NULL,
			'_SERVER' => array(
				'HTTPS' => NULL,
				'PATH_INFO' => '/',
				'HTTP_REFERER' => 'http://example.com/',
				'HTTP_USER_AGENT' => 'whatever (Mozilla 5.0/compatible)',
				'REMOTE_ADDR' => '127.0.0.1',
				'REQUEST_METHOD' => 'GET',
				'HTTP_X_REQUESTED_WITH' => 'ajax-or-something',
			),
			'_GET' => array(),
			'_POST' => array(),
		));

		$request = Request::fromGlobals();

		$this->assertSame(NULL, Request::$initial, 'Should not modify Request::$initial');
		$this->assertEquals('127.0.0.1',Request::$client_ip);
		$this->assertEquals('whatever (Mozilla 5.0/compatible)', Request::$user_agent);
		$this->assertEquals('HTTP/1.1', $request->protocol());
		$this->assertEquals('http://example.com/', $request->referrer());
		$this->assertEquals('ajax-or-something', $request->requested_with());
		$this->assertEquals([], $request->query());
		$this->assertEquals([], $request->post());
	}

	public function test_creates_client($client_class)
	{
		$request = Request::fromGlobals();
		$this->assertInstanceOf(Request_Client_Internal::class, $request->client());
	}

	/**
	 * Ensure that parameters can be read
	 *
	 * @test
	 */
	public function test_param()
	{
		$route   = new Route('(<controller>(/<action>(/<id>)))');
		$uri     = 'kohana_requesttest_dummy/foobar/some_id';
		$request = Request::with(
			[
				'routes' => [$route],
				'uri'    => $uri
			]
		);

		// We need to execute the request before it has matched a route
		$response = $request->execute();
		$controller = new Controller_Kohana_RequestTest_Dummy($request, $response);

		$this->assertSame(200, $response->status());
		$this->assertSame($controller->get_expected_response(), $response->body());
		$this->assertArrayHasKey('id', $request->param());
		$this->assertArrayNotHasKey('foo', $request->param());
		$this->assertEquals($request->uri(), $uri);

		// Ensure the params do not contain contamination from controller, action, route, uri etc etc
		$params = $request->param();

		// Test for illegal components
		$this->assertArrayNotHasKey('controller', $params);
		$this->assertArrayNotHasKey('action', $params);
		$this->assertArrayNotHasKey('directory', $params);
		$this->assertArrayNotHasKey('uri', $params);
		$this->assertArrayNotHasKey('route', $params);

		$route = new Route('(<uri>)', array('uri' => '.+'));
		$route->defaults(array('controller' => 'kohana_requesttest_dummy', 'action' => 'foobar'));

		$request = Request::with(
			[
				'routes' => [$route],
				'uri'    => 'kohana_requesttest_dummy'
			]
		);

		// We need to execute the request before it has matched a route
		$response = $request->execute();
		$controller = new Controller_Kohana_RequestTest_Dummy($request, $response);

		$this->assertSame(200, $response->status());
		$this->assertSame($controller->get_expected_response(), $response->body());
		$this->assertSame('kohana_requesttest_dummy', $request->param('uri'));
	}

	/**
	 * Tests Request::method()
	 *
	 * @test
	 * @testWith [null, "GET"]
	 *           ["GET", "GET"]
	 *           ["POST", "POST"]
	 */
	public function test_method($server_method, $expect)
	{
		if ($server_method) {
			$_SERVER['REQUEST_METHOD'] = $server_method;
		} else {
			unset($_SERVER['REQUEST_METHOD']);
		}
		$request = Request::fromGlobals('foo/bar');
		$this->assertEquals($expect, $request->method());
	}

	/**
	 * Tests Request::route()
	 *
	 * @test
	 */
	public function test_route()
	{
		$request = Request::with(['uri' => '']); // This should always match something, no matter what changes people make

		// We need to execute the request before it has matched a route
		try
		{
			$request->execute();
		}
		catch (Exception $e) {}

		$this->assertInstanceOf('Route', $request->route());
	}

	/**
	 * Tests Request::route()
	 *
	 * @test
	 */
	public function test_route_is_not_set_before_execute()
	{
		$request = Request::with(['uri' => '']); // This should always match something, no matter what changes people make

		// The route should be NULL since the request has not been executed yet
		$this->assertEquals($request->route(), NULL);
	}

	/**
	 * Tests Request::accept_type()
	 *
	 * @test
	 * @covers Request::accept_type
	 */
	public function test_accept_type()
	{
		$this->assertEquals(array('*/*' => 1), Request::accept_type());
	}

	/**
	 * Provides test data for Request::accept_lang()
	 * @return array
	 */
	public function provider_accept_lang()
	{
		return array(
			array('en-us', 1, array('_SERVER' => array('HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5'))),
			array('en-us', 1, array('_SERVER' => array('HTTP_ACCEPT_LANGUAGE' => 'en-gb'))),
			array('en-us', 1, array('_SERVER' => array('HTTP_ACCEPT_LANGUAGE' => 'sp-sp;q=0.5')))
		);
	}

	/**
	 * Tests Request::accept_lang()
	 *
	 * @test
	 * @covers Request::accept_lang
	 * @dataProvider provider_accept_lang
	 * @param array $params Query string
	 * @param string $expected Expected result
	 * @param array $enviroment Set environment
	 */
	public function test_accept_lang($params, $expected, $enviroment)
	{
		$this->setEnvironment($enviroment);

		$this->assertEquals(
			$expected,
			Request::accept_lang($params)
		);
	}

	/**
	 * Provides test data for Request::url()
	 * @return array
	 */
	public function provider_url()
	{
		return array(
			array(
				'foo/bar',
				'http',
				'http://localhost/kohana/foo/bar'
			),
			array(
				'foo',
				'http',
				'http://localhost/kohana/foo'
			),
			array(
				'0',
				'http',
				'http://localhost/kohana/0'
			)
		);
	}

	/**
	 * Tests Request::url()
	 *
	 * @test
	 * @dataProvider provider_url
	 * @covers Request::url
	 * @param string $uri the uri to use
	 * @param string $protocol the protocol to use
	 * @param array $expected The string we expect
	 */
	public function test_url($uri, $protocol, $expected)
	{
		if ( ! isset($_SERVER['argc']))
		{
			$_SERVER['argc'] = 1;
		}

		$this->setEnvironment(array(
			'Kohana::$base_url'  => '/kohana/',
			'_SERVER'            => array('HTTP_HOST' => 'localhost', 'argc' => $_SERVER['argc']),
			'Kohana::$index_file' => FALSE,
		));

		// issue #3967: inject the route so that we don't conflict with the application's default route
		$route = new Route('(<controller>(/<action>))');
		$route->defaults(array(
			'controller' => 'welcome',
			'action'     => 'index',
		));

		$request = \Request::with(['uri' => $uri, 'routes' => [$route]]);

		$this->assertEquals($expected, $request->url($protocol));
	}

	/**
	 * Data provider for test_set_protocol() test
	 *
	 * @return array
	 */
	public function provider_set_protocol()
	{
		return array(
			array(
				'http/1.1',
				'HTTP/1.1',
			),
			array(
				'ftp',
				'FTP',
			),
			array(
				'hTTp/1.0',
				'HTTP/1.0',
			),
		);
	}

	/**
	 * Tests the protocol() method
	 *
	 * @dataProvider provider_set_protocol
	 *
	 * @return null
	 */
	public function test_set_protocol($protocol, $expected)
	{
		// @todo: shouldn't protocol just come from the global $_SERVER not from HTTP::$protocol
		// which is then overridden in the standard bootstrap for unexplained reasons
		HTTP::$protocol = $protocol;
		$request = Request::fromGlobals();

		$this->assertSame($request->protocol(), $expected);
	}

	/**
	 * Provides data for test_post_max_size_exceeded()
	 * 
	 * @return  array
	 */
	public function provider_post_max_size_exceeded()
	{
		// Get the post max size
		$post_max_size = Num::bytes(ini_get('post_max_size'));

		return array(
			array(
				$post_max_size+200000,
				TRUE
			),
			array(
				$post_max_size-20,
				FALSE
			),
			array(
				$post_max_size,
				FALSE
			)
		);
	}

	/**
	 * Tests the post_max_size_exceeded() method
	 * 
	 * @dataProvider provider_post_max_size_exceeded
	 *
	 * @param   int      content_length 
	 * @param   bool     expected 
	 * @return  void
	 */
	public function test_post_max_size_exceeded($content_length, $expected)
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_LENGTH'] = $content_length;

		Request::$initial = Request::fromGlobals();

		$this->assertSame($expected, Request::post_max_size_exceeded());
	}

	/**
	 * Provides data for test_uri_only_trimed_on_internal()
	 *
	 * @return  array
	 */
	public function provider_uri_only_trimed_on_internal()
	{
		// issue #3967: inject the route so that we don't conflict with the application's default route
		$route = new Route('(<controller>(/<action>))');
		$route->defaults(array(
			'controller' => 'welcome',
			'action'     => 'index',
		));

		$result = array(
			array(
			    \Request::with(['uri' => 'foo/bar/']),
				'foo/bar'
			),
			array(
				\Request::with(['uri' => 'foo/bar']),
				'foo/bar'
			),
			array(
				\Request::with(['uri' => '/0']),
				'0'
			),
			array(
				\Request::with(['uri' => '0']),
				'0'
			),
			array(
				\Request::with(['uri' => '/']),
				'/'
			),
			array(
				\Request::with(['uri' => '']),
				'/'
			)
		);

		return $result;
	}

	/**
	 * Tests that the uri supplied to Request is only trimed
	 * for internal requests.
	 * 
	 * @dataProvider provider_uri_only_trimed_on_internal
	 *
	 * @return void
	 */
	public function test_uri_only_trimed_on_internal(Request $request, $expected)
	{
		$this->assertSame($request->uri(), $expected);
	}

	/**
	 * Provides data for test_headers_get()
	 *
	 * @return  array
	 */
	public function provider_headers_get()
	{
		$x_powered_by = 'Kohana Unit Test';
		$content_type = 'application/x-www-form-urlencoded';

		return [
			[
				[
					'HTTP_X_POWERED_BY' => $x_powered_by,
					'CONTENT_TYPE'      => $content_type
				],
				[
					'x-powered-by' => $x_powered_by,
					'content-type' => $content_type,
					'foobar'       => NULL
				]
			]
		];
	}

	/**
	 * Tests getting headers from the Request object
	 * 
	 * @dataProvider provider_headers_get
	 *
	 * @return  void
	 */
	public function test_headers_get($set_headers, $expect)
	{
		foreach ($set_headers as $key => $value) {
			$_SERVER[$key] = $value;
		}
		$request           = \Request::fromGlobals();
		foreach ($expect as $key => $expected_value) {
			$this->assertSame($expected_value, $request->headers($key), 'expect '.$key);
		}
	}

	/**
	 * Provides test data for test_query_parameter_access()
	 *
	 * @return  array
	 */
	public function provider_query_parameter_access()
	{
		return [
			[
				[
					'foo' => 'bar',
					'sna' => 'fu'
				],
				'foo',
				'bar'
			],
			[
				[
					'foo' => 'bar',
					'sna' => 'fu'
				],
				'bin',
				NULL
			],
			[
				[
					'foo' => 'bar',
					'sna' => 'fu'
				],
				NULL,
				[
					'foo' => 'bar',
					'sna' => 'fu'
				],
			]
		];
	}

	/**
	 * Tests that query parameters are parsed correctly
	 * 
	 * @dataProvider provider_query_parameter_access
	 *
	 * @param   string    url
	 * @param   array     query 
	 * @param   array    expected 
	 * @return  void
	 */
	public function test_query_parameter_access($get, $key, $expect)
	{
		$_GET             = $get;
		$this->assertSame($expect, \Request::fromGlobals()->query($key));
	}

	/**
	 * @expectedException \UnexpectedValueException
	 */
	public function test_throws_if_creating_with_query_string_in_url()
	{
		\Request::with(['uri' => 'some/url?with=a&query=string']);
	}

} // End Kohana_RequestTest


class Controller_Kohana_RequestTest_Dummy extends Controller
{
	// hard coded dummy response
	protected $dummy_response = "this is a dummy response";

	public function action_foobar()
	{
		$this->response->body($this->dummy_response);
	}

	public function get_expected_response()
	{
		return $this->dummy_response;
	}

} // End Kohana_RequestTest
