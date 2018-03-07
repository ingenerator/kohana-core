<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com
 */

class Request_Executor
{

	/**
	 * @var \Route[]
	 */
	protected $routes;

	/**
	 * @param \Route[] $routes
	 */
	public function __construct(array $routes)
	{
		$this->routes = $routes;
	}
	
	/**
	 * @param \Request $request
	 *
	 * @return \Response
	 */
	public function execute(\Request $request)
	{
		// Store the currently active request
		$previous = Request::$current;
		// Change the current request to this request
		Request::$current = $request;

		try {
			$this->route_request_and_assign_params($request);
			$response = Response::factory(['_protocol' => $request->protocol()]);
			$response = $this->execute_request($request, $response);

			if ( ! $response instanceof Response) {
				// Controller failed to return a Response.
				throw new Kohana_Exception('Controller failed to return a Response');
			}
		} catch (HTTP_Exception $e) {
			// Store the request context in the Exception
			if ($e->request() === NULL) {
				$e->request($request);
			}

			// Get the response via the Exception
			$response = $e->get_response();
		} catch (Exception $e) {
			// Generate an appropriate Response object
			$response = Kohana_Exception::_handler($e);
		}

		// Restore the previous request
		Request::$current = $previous;

		return $response;
	}

	/**
	 * @param \Request $request
	 */
	protected function route_request_and_assign_params(\Request $request)
	{
		$params = $this->find_matching_route_params($request);
		$request->set_matched_route_params($params);
	}

	/**
	 * @param \Request $request
	 *
	 * @return array
	 */
	protected function find_matching_route_params(\Request $request)
	{
		foreach ($this->routes as $name => $route) {
			// Use external routes for reverse routing only
			if ($route->is_external()) {
				continue;
			}

			if ($params = $route->matches($request)) {
				return array_merge(['action' => Route::$default_action], $params);
			}
		}

		throw HTTP_Exception::factory(
			'404',
			'Unable to find a route to match the URI: :uri',
			[':uri' => $request->uri()]
		)
			->request($request);
	}

	/**
	 * @param \Request $request
	 * @param          $response
	 *
	 * @return \Response
	 */
	protected function execute_request(\Request $request, $response)
	{
		$controller = $this->create_controller($request, $response);

		return $controller->execute();
	}

	/**
	 * @param \Request  $request
	 * @param \Response $response
	 *
	 * @return \Controller
	 * @throws \HTTP_Exception
	 * @throws \Kohana_Exception
	 */
	protected function create_controller(Request $request, Response $response)
	{
		$class = $this->get_controller_class_name($request);

		if ( ! class_exists($class)) {
			throw HTTP_Exception::factory(
				404,
				'The requested URL :uri was not found on this server.',
				[':uri' => $request->uri()]
			)->request($request);
		}

		$controller_refl = new ReflectionClass($class);

		if ($controller_refl->isAbstract()) {
			throw new Kohana_Exception(
				'Cannot create instances of abstract :controller',
				[':controller' => $class]
			);
		}

		return $controller_refl->newInstance($request, $response);
	}

	/**
	 * @param \Request $request
	 *
	 * @return string
	 */
	protected function get_controller_class_name(Request $request)
	{
		$controller = $request->controller();
		if (substr($controller, 0, 1) === '\\') {
			// Use the FQCN with no prefix / directory / etc
			return $controller;
		}

		$prefix    = 'Controller_';
		$directory = $request->directory();
		if ($directory) {
			// Add the directory name to the class prefix
			$prefix .= str_replace(['\\', '/'], '_', trim($directory, '/')).'_';
		}

		return $prefix.$controller;
	}

}
