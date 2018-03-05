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
		Request::$initial = new Request('/');
	}

	// @codingStandardsIgnoreStart
	public function tearDown()
	// @codingStandardsIgnoreEnd
	{
		Request::$initial = $this->_initial_request;
		parent::tearDown();
	}

	public function test_initial()
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

		$request = Request::factory();

		$this->assertEquals(Request::$initial, $request);

		$this->assertEquals(Request::$client_ip, '127.0.0.1');

		$this->assertEquals(Request::$user_agent, 'whatever (Mozilla 5.0/compatible)');

		$this->assertEquals($request->protocol(), 'HTTP/1.1');

		$this->assertEquals($request->referrer(), 'http://example.com/');

		$this->assertEquals($request->requested_with(), 'ajax-or-something');

		$this->assertEquals($request->query(), array());

		$this->assertEquals($request->post(), array());
	}

	/**
	 * Tests that the allow_external flag prevents an external request.
	 *
	 * @return null
	 */
	public function test_disable_external_tests()
	{
		$this->setEnvironment(
			array(
				'Request::$initial' => NULL,
			)
		);

		$request = new Request('http://www.google.com/', array(), FALSE);

		$this->assertEquals(FALSE, $request->is_external());
	}

	/**
	 * Provides the data for test_create()
	 * @return  array
	 */
	public function provider_create()
	{
		return array(
			array('foo/bar', 'Request_Client_Internal'),
		);
	}

	/**
	 * Ensures the create class is created with the correct client
	 *
	 * @test
	 * @dataProvider provider_create
	 */
	public function test_create($uri, $client_class)
	{
		$request = Request::factory($uri);

		$this->assertInstanceOf($client_class, $request->client());
	}

	/**
	 * Ensure that parameters can be read
	 *
	 * @test
	 */
	public function test_param()
	{
		$route = new Route('(<controller>(/<action>(/<id>)))');

		$uri = 'kohana_requesttest_dummy/foobar/some_id';
		$request = Request::factory($uri, NULL, TRUE, array($route));

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
		$request = Request::factory('kohana_requesttest_dummy', NULL, TRUE, array($route));

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
		Request::$initial = NULL;

		$request = Request::factory('foo/bar');
		$this->assertEquals($expect, $request->method());
	}

	/**
	 * Tests Request::route()
	 *
	 * @test
	 */
	public function test_route()
	{
		$request = Request::factory(''); // This should always match something, no matter what changes people make

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
		$request = Request::factory(''); // This should always match something, no matter what changes people make

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

		$this->assertEquals(Request::factory($uri, array(), TRUE, array($route))->url($protocol), $expected);
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
		Request::$initial = NULL;
		$request = Request::factory();

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

		Request::$initial = NULL;
		Request::factory();

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

		$old_request = Request::$initial;
		Request::$initial = new Request(TRUE, array(), TRUE, array($route));

		$result = array(
			array(
				new Request('foo/bar/'),
				'foo/bar'
			),
			array(
				new Request('foo/bar'),
				'foo/bar'
			),
			array(
				new Request('/0'),
				'0'
			),
			array(
				new Request('0'),
				'0'
			),
			array(
				new Request('/'),
				'/'
			),
			array(
				new Request(''),
				'/'
			)
		);

		Request::$initial = $old_request;
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
		\Request::$initial = NULL;
		$request           = \Request::factory();
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
		Request::$initial = NULL;
		$_GET             = $get;
		$request          = \Request::factory();
		$this->assertSame($expect, $request->query($key));
	}

	/**
	 * @expectedException \UnexpectedValueException
	 */
	public function test_throws_if_creating_with_query_string_in_url()
	{
		new \Request('some/url?with=a&query=string');
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
