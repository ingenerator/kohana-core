<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Request Client for internal execution
 *
 * @package        Kohana
 * @category       Base
 * @author         Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license        http://kohanaframework.org/license
 * @since          3.1.0
 */
class Kohana_Request_Client_Internal extends Request_Client
{

	/**
	 * Processes the request, executing the controller action that handles this
	 * request, determined by the [Route].
	 *
	 *     $request->execute();
	 *
	 * @param   Request $request
	 *
	 * @return  Response
	 * @throws  Kohana_Exception
	 * @uses    [Kohana::$profiling]
	 * @uses    [Profiler]
	 */
	public function execute_request(Request $request, Response $response)
	{

		if (Kohana::$profiling) {
			// Set the benchmark name
			$benchmark = '"'.$request->uri().'"';

			if ($request !== Request::$initial AND Request::$current) {
				// Add the parent request uri
				$benchmark .= ' « "'.Request::$current->uri().'"';
			}

			// Start benchmarking
			$benchmark = Profiler::start('Requests', $benchmark);
		}

		// Store the currently active request
		$previous = Request::$current;

		// Change the current request to this request
		Request::$current = $request;

		// Is this the initial request
		$initial_request = ($request === Request::$initial);

		try {
			$controller = $this->create_controller($request, $response);
			$response   = $controller->execute();

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

		if (isset($benchmark)) {
			// Stop the benchmark
			Profiler::stop($benchmark);
		}

		// Return the response
		return $response;
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
