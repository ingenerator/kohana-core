<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Request Client. Processes a [Request] and handles [HTTP_Caching] if
 * available. Will usually return a [Response] object as a result of the
 * request unless an unexpected error occurs.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 * @since      3.1.0
 */
abstract class Kohana_Request_Client {

	/**
	 * Processes the request, executing the controller action that handles this
	 * request, determined by the [Route].
	 *
	 * 1. Before the controller action is called, the [Controller::before] method
	 * will be called.
	 * 2. Next the controller action will be called.
	 * 3. After the controller action is called, the [Controller::after] method
	 * will be called.
	 *
	 * By default, the output from the controller is captured and returned, and
	 * no headers are sent.
	 *
	 *     $request->execute();
	 *
	 * @param   Request   $request
	 * @param   Response  $response
	 * @return  Response
	 * @throws  Kohana_Exception
	 * @uses    [Kohana::$profiling]
	 * @uses    [Profiler]
	 */
	public function execute(Request $request)
	{
		// Execute the request and pass the currently used protocol
        $response = $this->execute_request(
            $request,
            Response::factory(['_protocol' => $request->protocol()])
        );

		return $response;
	}

	/**
	 * Processes the request passed to it and returns the response from
	 * the URI resource identified.
	 *
	 * This method must be implemented by all clients.
	 *
	 * @param   Request   $request   request to execute by client
	 * @param   Response  $response
	 * @return  Response
	 * @since   3.2.0
	 */
	abstract public function execute_request(Request $request, Response $response);


}
