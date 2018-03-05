<?php defined('SYSPATH') OR die('No direct script access.');
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
     * Gets or sets the HTTP protocol. The standard protocol to use
     * is `HTTP/1.1`.
     *
     * @param   string   $protocol  Protocol to set to the request/response
     * @return  mixed
     */
    public function protocol($protocol = NULL);

    /**
     * Gets or sets HTTP headers to the request or response. All headers
     * are included immediately after the HTTP protocol definition during
     * transmission. This method provides a simple array or key/value
     * interface to the headers.
     *
     * @param   mixed   $key    Key or array of key/value pairs to set
     * @param   string  $value  Value to set to the supplied key
     * @return  mixed
     */
    public function headers($key = NULL, $value = NULL);

    /**
     * Gets or sets the HTTP body to the request or response. The body is
     * included after the header, separated by a single empty new line.
     *
     * @param   string    $content  Content to set to the object
     * @return  string
     * @return  void
     */
    public function body($content = NULL);

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
	 * Gets or sets the HTTP method. Usually GET, POST, PUT or DELETE in
	 * traditional CRUD applications.
	 *
	 * @param   string   $method  Method to use for this request
	 * @return  mixed
	 */
	public function method($method = NULL);

	/**
	 * Gets the URI of this request, optionally allows setting
	 * of [Route] specific parameters during the URI generation.
	 * If no parameters are passed, the request will use the
	 * default values defined in the Route.
	 *
	 * @param   array    $params  Optional parameters to include in uri generation
	 * @return  string
	 */
	public function uri();

	/**
	 * Gets or sets HTTP query string.
	 *
	 * @param   mixed   $key    Key or key value pairs to set
	 * @param   string  $value  Value to set to a key
	 * @return  mixed
	 */
	public function query($key = NULL, $value = NULL);

	/**
	 * Gets or sets HTTP POST parameters to the request.
	 *
	 * @param   mixed   $key   Key or key value pairs to set
	 * @param   string  $value Value to set to a key
	 * @return  mixed
	 */
	public function post($key = NULL, $value = NULL);

}
