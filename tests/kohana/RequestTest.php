<?php defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Unit tests for request class
 *
 * @group          kohana
 * @group          kohana.core
 * @group          kohana.core.request
 *
 * @package        Kohana
 * @category       Tests
 * @author         Kohana Team
 * @author         BRMatt <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license        http://kohanaframework.org/license
 */
class Kohana_RequestTest extends Unittest_TestCase
{
	protected $_inital_request;

	// @codingStandardsIgnoreStart
	public function setUp(): void
		// @codingStandardsIgnoreEnd
	{
		parent::setUp();
		Kohana::$config->load('url')->set('trusted_hosts', ['localhost']);
	}

	// @codingStandardsIgnoreStart
	public function tearDown(): void
		// @codingStandardsIgnoreEnd
	{
		parent::tearDown();
	}

	public function test_its_factory_just_throws()
	{
		$this->expectException(BadMethodCallException::class);
		Request::factory();
	}

	public function test_init_initial_sets_the_global_initial_and_related_state()
	{
		$this->setEnvironment(
			[
				'Request::$initial'    => NULL,
				'Request::$client_ip'  => NULL,
				'Request::$user_agent' => NULL,
			]
		);
		$rq = Request::with(['client_ip' => '182.132.123.145', 'client_user_agent' => 'tesla smartcar']);
		$this->assertSame($rq, Request::initInitial($rq), 'initInitial should return request');
		$this->assertSame($rq, Request::initial(), 'initInitial should init request');
		$this->assertSame('182.132.123.145', Request::$client_ip, 'should set ::$client_ip');
		$this->assertSame('tesla smartcar', Request::$user_agent, 'should set ::$user_agent');
	}

	public function test_from_globals_loads_from_global_state()
	{
		$this->setEnvironment([
			'Request::$initial'    => NULL,
			'Request::$client_ip'  => NULL,
			'Request::$user_agent' => NULL,
			'_SERVER'              => [
				'HTTPS'                 => NULL,
				'PATH_INFO'             => '/',
				'HTTP_REFERER'          => 'http://example.com/',
				'HTTP_USER_AGENT'       => 'whatever (Mozilla 5.0/compatible)',
				'REMOTE_ADDR'           => '127.0.0.1',
				'REQUEST_METHOD'        => 'GET',
				'HTTP_X_REQUESTED_WITH' => 'ajax-or-something',
			],
			'_GET'                 => [],
			'_POST'                => [],
		]);

		$request = Request::fromGlobals();

		$this->assertSame(NULL, Request::$initial, 'Should not modify Request::$initial');
		$this->assertSame(NULL, Request::$client_ip, 'Should not modify Request::$client_ip');
		$this->assertSame(NULL, Request::$user_agent, 'Should not modify Request::$user_agent');
		$this->assertEquals('127.0.0.1', $request->client_ip());
		$this->assertEquals('whatever (Mozilla 5.0/compatible)', $request->client_user_agent());
		$this->assertEquals('HTTP/1.1', $request->protocol());
		$this->assertEquals('http://example.com/', $request->referrer());
		$this->assertEquals('ajax-or-something', $request->requested_with());
		$this->assertEquals([], $request->query());
		$this->assertEquals([], $request->post());
	}

	public function provider_incoming_uri()
	{
		return [
			[
				// PATH_INFO over all else
				[
					'PATH_INFO'    => '/foo/bar',
					'REQUEST_URI'  => 'well this broke',
					'PHP_SELF'     => 'also wrong',
					'REDIRECT_URL' => 'Wrongty wrong',
				],
				'foo/bar',
			],
			[
				// No PATH_INFO - omg! Use REQUEST_URI - valid URL PATH
				['REQUEST_URI' => '/myscript.php'],
				'myscript.php',
			],
			[
				// No PATH_INFO - omg! Use REQUEST_URI - valid URL PATH and lose the querystring
				['REQUEST_URI' => '/myscript.php?lose=me'],
				'myscript.php',
			],
			[
				// No PATH_INFO - omg! Use REQUEST_URI - but parse_url says there's an invalid URL PATH
				// Note can't use the example in the code comment as :// in url triggers our error
				// on attempting an external request
				['REQUEST_URI' => '//judge.php'],
				'judge.php',
			],
			[
				// No PATH_INFO - omg! Use REQUEST_URI - but parse_url says there's an invalid URL PATH
				// And they sent a querystring too, the loons
				['REQUEST_URI' => '//judge.php?lose=me'],
				'judge.php',
			],
			[
				// No PATH_INFO and it's the docroot (this does happen btw)
				['REQUEST_URI' => '/'],
				'/',
			],
			[
				// No PATH_INFO and it's the docroot (this does happen btw)
				['REQUEST_URI' => '/?fo=1'],
				'/',
			],
		];
	}

	/**
	 * @dataProvider provider_incoming_uri
	 */
	public function test_from_globals_detects_incoming_uri($server, $expect)
	{
		$this->setEnvironment(
			[
				'Request::$initial'    => NULL,
				'Request::$client_ip'  => NULL,
				'Request::$user_agent' => NULL,
				'_SERVER'              => $server,
				'_GET'                 => [],
				'_POST'                => [],
			]
		);
		$request = Request::fromGlobals();
		$this->assertSame($expect, $request->uri());
	}

	/**
	 * Ensure that parameters can be read
	 *
	 * @test
	 */
	public function test_assigns_route_params_and_splits_out_directory_controller_action()
	{
		$request = Request::with([]);
		$request->set_matched_route_params(
			[
				'directory'  => 'any',
				'controller' => 'thing',
				'action'     => 'delete',
				'event_id'   => 21,
				'format'     => 'png',
			]
		);
		$this->assertSame('any', $request->directory(), 'Should assign directory');
		$this->assertSame('thing', $request->controller(), 'Should assign controller');
		$this->assertSame('delete', $request->action(), 'Should assign action');
		$this->assertSame(['event_id' => 21, 'format' => 'png'], $request->param());
		$this->assertSame(21, $request->param('event_id'), 'should have single param');
		$this->assertSame(NULL, $request->param('missing'), 'should default missing param null');
		$this->assertSame(
			15,
			$request->param('missing', 15),
			'should default missing param to provided'
		);
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
	 * Tests Request::accept_type()
	 *
	 * @test
	 * @covers Request::accept_type
	 */
	public function test_accept_type()
	{
		$this->assertEquals(['*/*' => 1], Request::accept_type());
	}

	/**
	 * Provides test data for Request::accept_lang()
	 *
	 * @return array
	 */
	public function provider_accept_lang()
	{
		return [
			['en-us', 1, ['_SERVER' => ['HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5']]],
			['en-us', 1, ['_SERVER' => ['HTTP_ACCEPT_LANGUAGE' => 'en-gb']]],
			['en-us', 1, ['_SERVER' => ['HTTP_ACCEPT_LANGUAGE' => 'sp-sp;q=0.5']]],
		];
	}

	/**
	 * Tests Request::accept_lang()
	 *
	 * @test
	 * @covers       Request::accept_lang
	 * @dataProvider provider_accept_lang
	 *
	 * @param array  $params     Query string
	 * @param string $expected   Expected result
	 * @param array  $enviroment Set environment
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
	 *
	 * @return array
	 */
	public function provider_url()
	{
		return [
			[
				'foo/bar',
				'http',
				'http://localhost/kohana/foo/bar',
			],
			[
				'foo',
				'http',
				'http://localhost/kohana/foo',
			],
			[
				'0',
				'http',
				'http://localhost/kohana/0',
			],
		];
	}

	/**
	 * Tests Request::url()
	 *
	 * @test
	 * @dataProvider provider_url
	 * @covers       Request::url
	 *
	 * @param string $uri      the uri to use
	 * @param string $protocol the protocol to use
	 * @param array  $expected The string we expect
	 */
	public function test_url($uri, $protocol, $expected)
	{
		if ( ! isset($_SERVER['argc'])) {
			$_SERVER['argc'] = 1;
		}

		$this->setEnvironment([
			'Kohana::$base_url'   => '/kohana/',
			'_SERVER'             => ['HTTP_HOST' => 'localhost', 'argc' => $_SERVER['argc']],
			'Kohana::$index_file' => FALSE,
		]);

		$request = Request::with(['uri' => $uri]);

		$this->assertEquals($expected, $request->url($protocol));
	}

	/**
	 * Data provider for test_set_protocol() test
	 *
	 * @return array
	 */
	public function provider_set_protocol()
	{
		return [
			[
				'http/1.1',
				'HTTP/1.1',
			],
			[
				'ftp',
				'FTP',
			],
			[
				'hTTp/1.0',
				'HTTP/1.0',
			],
		];
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
		$request        = Request::fromGlobals();

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

		return [
			[
				$post_max_size + 200000,
				TRUE,
			],
			[
				$post_max_size - 20,
				FALSE,
			],
			[
				$post_max_size,
				FALSE,
			],
		];
	}

	/**
	 * Tests the post_max_size_exceeded() method
	 *
	 * @dataProvider provider_post_max_size_exceeded
	 *
	 * @param int      content_length
	 * @param bool     expected
	 *
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
		$route->defaults([
			'controller' => 'welcome',
			'action'     => 'index',
		]);

		$result = [
			[
				Request::with(['uri' => 'foo/bar/']),
				'foo/bar',
			],
			[
				Request::with(['uri' => 'foo/bar']),
				'foo/bar',
			],
			[
				Request::with(['uri' => '/0']),
				'0',
			],
			[
				Request::with(['uri' => '0']),
				'0',
			],
			[
				Request::with(['uri' => '/']),
				'/',
			],
			[
				Request::with(['uri' => '']),
				'/',
			],
		];

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
					'CONTENT_TYPE'      => $content_type,
				],
				[
					'x-powered-by' => $x_powered_by,
					'content-type' => $content_type,
					'foobar'       => NULL,
				],
			],
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
		$request = Request::fromGlobals();
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
					'sna' => 'fu',
				],
				'foo',
				'bar',
			],
			[
				[
					'foo' => 'bar',
					'sna' => 'fu',
				],
				'bin',
				NULL,
			],
			[
				[
					'foo' => 'bar',
					'sna' => 'fu',
				],
				NULL,
				[
					'foo' => 'bar',
					'sna' => 'fu',
				],
			],
		];
	}

	/**
	 * Tests that query parameters are parsed correctly
	 *
	 * @dataProvider provider_query_parameter_access
	 *
	 * @param string    url
	 * @param array     query
	 * @param array    expected
	 *
	 * @return  void
	 */
	public function test_query_parameter_access($get, $key, $expect)
	{
		$_GET = $get;
		$this->assertSame($expect, Request::fromGlobals()->query($key));
	}

	public function test_creating_with_questionmark_in_url_creates_with_literal_url_and_no_query()
	{
		$rq = Request::with(['uri' => 'some/url?with=a&query=string']);
		$this->assertSame('some/url?with=a&query=string', $rq->uri());
		$this->assertSame([], $rq->query());
	}

	public function test_it_trims_basic_string_value()
	{
		$rq     = Request::with(['post' => ['foo' => ' hello   ']]);
		$result = $rq->trimmedPost();
		$this->assertSame('hello', $result['foo']);
	}

	public function test_empty_strings_survive_trimming()
	{
		$rq     = Request::with(['post' => ['empty' => '', 'whitespace' => '  ']]);
		$result = $rq->trimmedPost();
		$this->assertSame(['empty' => '', 'whitespace' => ''], $result);
	}

	public function test_it_trims_recursively()
	{
		$rq     = Request::with(
			[
				'post' => [
					'addresses' => [
						'home' => [
							'street' => ' 123  Sesame Street ',
							'city'   => ' Avenue  Q   ',
						],
						'away' => [
							'street' => 'The  Beach ',
							'city'   => ' Melbourne  ,  Oz  ',
						],
						'name' => ' Elmo  ',
					],
				],
			]
		);
		$result = $rq->trimmedPost();
		$this->assertSame(
			[
				'addresses' => [
					'home' => [
						'street' => '123  Sesame Street',
						'city'   => 'Avenue  Q',
					],
					'away' => [
						'street' => 'The  Beach',
						'city'   => 'Melbourne  ,  Oz',
					],
					'name' => 'Elmo',
				],
			],
			$result
		);
	}

} // End Kohana_RequestTest
