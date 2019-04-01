<?php \defined('SYSPATH') OR die('No direct script access.');
/**
 * A HTTP Request specific interface that adds the methods required
 * by HTTP requests. Over and above [Kohana_HTTP_Interaction], this
 * interface provides method, uri, get and post methods.
 *
 * @package    Kohana
 * @category   HTTP
 * @author     Kohana Team
 * @since      3.1.0
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    http://kohanaframework.org/license
 */
interface Kohana_HTTP_Request {

	// HTTP Methods
	const GET       = 'GET';
	const POST      = 'POST';
	const PUT       = 'PUT';
	const DELETE    = 'DELETE';
	const HEAD      = 'HEAD';
	const OPTIONS   = 'OPTIONS';
	const TRACE     = 'TRACE';
	const CONNECT   = 'CONNECT';

    /**
     * Gets the HTTP protocol. The standard protocol to use is `HTTP/1.1`.
     *
     * @return  mixed
     */
    public function protocol();

    /**
     * Gets HTTP headers from the request.
     *
     * @param   mixed   $key    Key to get or null for an array
     * @return  string|array
     */
    public function headers($key = NULL);

    /**
     * Gets the HTTP body from the request.
     *
     * @return  string
     */
    public function body();

    /**
     * Renders the HTTP_Interaction to a string, producing
     *
     *  - Protocol
     *  - Headers
     *  - Body
     *
     * @return  string
     */
    public function render();

	/**
	 * Gets the HTTP method. Usually GET, POST, PUT or DELETE in
	 * traditional CRUD applications.
	 *
	 * @return  string
	 */
	public function method();

	/**
	 * Gets the URI of this request.
	 *
	 * @return  string
	 */
	public function uri();

	/**
	 * Gets HTTP query string values
	 *
	 * @param   mixed   $key    Key to get, or null for the full array
	 * @return  mixed
	 */
	public function query($key = NULL);

	/**
	 * Gets HTTP POST parameters from the request.
	 *
	 * @param   mixed   $key   Key to get, or null for full array
	 * @return  mixed
	 */
	public function post($key = NULL);

}
