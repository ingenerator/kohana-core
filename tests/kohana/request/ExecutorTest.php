<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

class Kohana_Request_ExecutorTest extends \PHPUnit\Framework\TestCase
{
	protected $routes = [];

	public function test_it_throws_404_exception_if_no_matching_route()
	{
		$this->routes = [new Route('foo')];
        $subject = $this->newSubject();

        $this->expectException(\HTTP_Exception_404::class);
        $subject->execute(\Request::with(['uri' => 'bar']));
	}

	public function test_it_throws_404_exception_if_controller_class_does_not_exist()
	{
		$this->routes = [new Route('<controller>/<action>')];
		$subject = $this->newSubject();

        $this->expectException(\HTTP_Exception_404::class);
		$subject->execute(\Request::with(['uri' => 'no/controller']));
	}

	public function test_it_throws_generic_exception_if_controller_is_abstract()
	{
        $this->routes = [new Route('<controller>/<action>')];
        $subject      = $this->newSubject();

        $this->expectException(Kohana_Exception::class);
        $this->expectExceptionMessage('abstract');
        $subject->execute(\Request::with(['uri' => 'template/anything']));
	}

	public function test_it_coalesces_request_into_and_bubbles_http_exception_thrown_by_controller()
	{
		$this->routes = [new Route('<controller>/<action>')];

		Controller_ThrowsException::$exception = HTTP_Exception::factory(302);
		Controller_ThrowsException::$exception->location('http://anywhere');

		$rq = \Request::with(['uri' => 'throwsexception/anything']);
		try {
		    $this->newSubject()->execute($rq);
		    $this->fail('Expected exception, none got');
		} catch (\HTTP_Exception_302 $e) {
			$this->assertSame(Controller_ThrowsException::$exception, $e);
			$this->assertSame($e->request(), $rq);
		}
	}

	public function test_it_bubbles_generic_controller_exception()
	{
		$this->routes = [new Route('<controller>/<action>')];

		Controller_ThrowsException::$exception = new \InvalidArgumentException('I broke');

		$subject = $this->newSubject();

        $this->expectException(\InvalidArgumentException::class);
		$subject->execute(\Request::with(['uri' => 'throwsexception/anything']));
	}

	public function test_it_throws_if_controller_does_not_return_response()
    {
        $this->routes = [new Route('<controller>/<action>')];
        $subject      = $this->newSubject();

        $this->expectException(Kohana_Exception::class);
        $this->expectExceptionMessage('return a Response');
        $subject->execute(\Request::with(['uri' => 'emptyreturn/anything']));
	}

	public function test_it_sets_response_protocol_to_request_protocol()
	{
		$this->routes = [new Route('<controller>/<action>')];
		$response     = $this->newSubject()->execute(
			\Request::with(['uri' => 'welcome/index', 'protocol' => 'HTTP/12.5'])
		);
		$this->assertSame('HTTP/12.5', $response->protocol());
	}

	public function provider_route_param_test()
	{
		return [
			[
				'requestcapture',
				[
					'directory'  => NULL,
					'controller' => 'Requestcapture',
					'action'     => 'index',
					'params'     => []
				]
			],
			[
				'requestcapture/whatever',
				[
					'directory'  => NULL,
					'controller' => 'Requestcapture',
					'action'     => 'whatever',
					'params'     => []
				]
			],
			[
				'foo/anything/12',
				[
					'directory'  => NULL,
					'controller' => 'Requestcapture',
					'action'     => 'anything',
					'params'     => ['id' => 12]
				]
			],

		];
	}

	/**
	 * @dataProvider provider_route_param_test
	 */
	public function test_it_executes_with_first_matching_route_and_assigns_params_to_request(
		$url,
		$expect
	) {
		$this->routes = [
			$this->givenRouteWithDefaults('foo/<action>/<id>', ['controller' => 'requestcapture']),
			$this->givenRouteWithDefaults(
				'(<controller>)(/<action>)',
				['controller' => 'requestcapture']
			),
		];

		$response = $this->newSubject()->execute(\Request::with(['uri' => $url]));

		$this->assertSame(200, $response->status());
		$this->assertEquals($expect, \json_decode($response, TRUE));
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
				[
					'directory'  => NULL,
					'controller' => '\My\Test\TestController',
					'action'     => 'things'
				],
				'\My\Test\TestController',
				'My\Test\TestController::things'
			],

		];
	}

	/**
	 * @dataProvider provider_controller_class
	 */
	public function test_it_executes_with_expected_controller_class(
		$route_defaults,
		$controller_class,
		$expect
	) {
		if ( ! \class_exists($controller_class)) {
			$this->defineExtensionClass($controller_class, ClassReturningController::class);
		}
		$this->routes = [$this->givenRouteWithDefaults('', $route_defaults)];
		$response     = $this->newSubject()->execute(\Request::with(['uri' => '/']));
		$this->assertEquals($expect, $response->body());
	}

	protected function defineExtensionClass($fqcn, $base_class)
	{
		$parts = \array_filter(\explode('\\', $fqcn));
		$class = \array_pop($parts);
		$ns    = \implode('\\', $parts);
		if ($ns) {
			$ns = 'namespace '.$ns.';';
		}
		eval("$ns class $class extends \\$base_class {}");
	}

	/**
	 * @return \Request_Executor
	 */
	protected function newSubject()
	{
		$subject = new Request_Executor($this->routes);

		return $subject;
	}

	/**
	 * @param $uri
	 * @param $route_defaults
	 * @param $regex
	 *
	 * @return \Route
	 */
	protected function givenRouteWithDefaults($uri, array $route_defaults, $regex = NULL)
	{
		$route = new Route($uri, $regex);
		$route->defaults($route_defaults);

		return $route;
	}

}

class ClassReturningController extends Controller
{

	public function execute()
	{
		$this->response->body(\get_class($this).'::'.$this->request->action());

		return $this->response;
	}
}

class Controller_ThrowsException extends Controller
{
	public static $exception;

	public function execute()
	{
		throw static::$exception;
	}
}

class Controller_EmptyReturn extends Controller
{
	public function execute()
	{
		// Do nothing
	}
}

class Controller_RequestCapture extends Controller
{
	public function execute()
	{
		$this->response->body(
			\json_encode(
				[
					'directory'  => $this->request->directory(),
					'controller' => $this->request->controller(),
					'action'     => $this->request->action(),
					'params'     => $this->request->param()
				]
			)
		);

		return $this->response;
	}
}
