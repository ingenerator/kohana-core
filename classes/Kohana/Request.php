<?php \defined('SYSPATH') OR die('No direct script access.');
/**
 * Request. Uses the [Route] class to determine what
 * [Controller] to send the request to.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Request implements HTTP_Request {

	/**
	 * @var  string  client user agent
	 * @deprecated
	 * @see \Request::client_user_agent()
	 */
	public static $user_agent = '';

	/**
	 * @var  string  client IP address
	 * @deprecated
	 * @see \Request::client_ip()
	 */
	public static $client_ip = '0.0.0.0';

	/**
	 * @var  string  trusted proxy server IPs
	 */
	public static $trusted_proxies = array('127.0.0.1', 'localhost', 'localhost.localdomain');

	/**
	 * @var  Request  main request instance
	 */
	public static $initial;

	/**
	 * Creates a new request object for the given URI. New requests should be
	 * Created using the [Request::factory] method.
	 *
	 *     $request = Request::factory($uri);
	 *
	 * If $cache parameter is set, the response for the request will attempt to
	 * be retrieved from the cache.
	 *
	 * @param   string  $uri              URI of the request
	 * @return  void|Request
	 * @throws  Request_Exception
	 * @uses    Route::all
	 * @uses    Route::matches
	 */
	public static function factory($uri = TRUE)
	{
		throw new \BadMethodCallException('Unexpected call to removed '.__METHOD__.' see ::fromGlobals() or ::with()');
	}

	/**
	 * Make this the initial request
	 *
	 * @param \Request $request
	 *
	 * @return \Request
	 */
	public static function initInitial(\Request $request)
	{
		\Request::$initial    = $request;
		\Request::$client_ip  = $request->client_ip();
		\Request::$user_agent = $request->client_user_agent();

		return $request;
	}

	public static function fromGlobals()
	{
		$props = [
			'protocol'          => \strtoupper(HTTP::$protocol),
			'method'            => \strtoupper(\Arr::get($_SERVER, 'REQUEST_METHOD', \Request::GET)),
			'uri'               => static::detect_uri(),
			'secure'            => static::detect_is_secure(),
			'referrer'          => \Arr::get($_SERVER, 'HTTP_REFERER', NULL),
			'requested_with'    => \Arr::get($_SERVER, 'HTTP_X_REQUESTED_WITH', NULL),
			'body'              => NULL,
			'cookies'           => [],
			'get'               => $_GET,
			'post'              => $_POST,
			'header'            => HTTP::request_headers(),
			'client_ip'         => static::detect_client_ip() ?: '0.0.0.0',
			'client_user_agent' => \Arr::get($_SERVER, 'HTTP_USER_AGENT', NULL),
		];

		if ($props['requested_with']) {
			$props['requested_with'] = \strtolower($props['requested_with']);
		}

		if ($props['method'] !== HTTP_Request::GET) {
			// Ensure the raw body is saved for future use
			$props['body'] = \file_get_contents('php://input');
		}

		foreach (\array_keys($_COOKIE) as $cookie_key) {
			$props['cookies'][$cookie_key] = Cookie::get($cookie_key);
		}

		return static::with($props);
	}

	public static function with(array $properties)
	{
		$request = new \Request(\Arr::get($properties, 'uri', NULL));
		// NB: `uri` still has special status because it gets trimmed, need to leave it to the constructor
		unset($properties['uri']);
		// @todo: safety check properties match expected
		foreach ($properties as $key => $value) {
			$prop = '_'.$key;
			$request->$prop = $value;
		}
		return $request;
	}

	/**
	 * Automatically detects the URI of the main request using PATH_INFO,
	 * REQUEST_URI, PHP_SELF or REDIRECT_URL.
	 *
	 *     $uri = Request::detect_uri();
	 *
	 * @return  string  URI of the main request
	 * @throws  Kohana_Exception
	 * @since   3.0.8
	 */
	protected static function detect_uri()
	{
		if ( ! empty($_SERVER['PATH_INFO']))
		{
			// PATH_INFO does not contain the docroot or index
			$uri = $_SERVER['PATH_INFO'];
		}
		else
		{
			// REQUEST_URI and PHP_SELF include the docroot and index

			if (isset($_SERVER['REQUEST_URI']))
			{
				$uri = \parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

				if ( ! $uri) 
				{
					/**
					 * We use REQUEST_URI as the fallback value. The reason
					 * for this is we might have a malformed URL such as:
					 *
					 *  http://localhost/http://example.com/judge.php
					 *
					 * which parse_url can't handle. So rather than leave empty
					 * handed, we'll use this.
					 *
					 * This also covers urls that are not actually malformed but parse_url dislikes
					 * such as `//index.php`.
					 *
					 * However, REQUEST_URI may include a querystring if one was provided, and we
					 * need to strip that off so it behaves the same as parse_url would - otherwise
					 * the request itself gives an exception on construction because of the QS in
					 * the URL.
					 */
					 $uri = explode('?', $_SERVER['REQUEST_URI'])[0];
				}

				// Decode the request URI
				$uri = \rawurldecode($uri);
			}
			elseif (isset($_SERVER['PHP_SELF']))
			{
				$uri = $_SERVER['PHP_SELF'];
			}
			elseif (isset($_SERVER['REDIRECT_URL']))
			{
				$uri = $_SERVER['REDIRECT_URL'];
			}
			else
			{
				// If you ever see this error, please report an issue at http://dev.kohanaphp.com/projects/kohana3/issues
				// along with any relevant information about your web server setup. Thanks!
				throw new Kohana_Exception('Unable to detect the URI using PATH_INFO, REQUEST_URI, PHP_SELF or REDIRECT_URL');
			}

			// Get the path from the base URL, including the index file
			$base_url = \parse_url(Kohana::$base_url, PHP_URL_PATH);

			if (\strpos($uri, $base_url) === 0)
			{
				// Remove the base URL from the URI
				$uri = (string) \substr($uri, \strlen($base_url));
			}

			if (Kohana::$index_file AND \strpos($uri, Kohana::$index_file) === 0)
			{
				// Remove the index file from the URI
				$uri = (string) \substr($uri, \strlen(Kohana::$index_file));
			}
		}

		return $uri;
	}

	/**
	 * @return string
	 */
	protected static function detect_client_ip()
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
			AND isset($_SERVER['REMOTE_ADDR'])
			AND \in_array($_SERVER['REMOTE_ADDR'], Request::$trusted_proxies)) {
			// Use the forwarded IP address, typically set when the
			// client is using a proxy server.
			// Format: "X-Forwarded-For: client1, proxy1, proxy2"
			$client_ips = \explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

			return \array_shift($client_ips);

		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])
			AND isset($_SERVER['REMOTE_ADDR'])
			AND \in_array($_SERVER['REMOTE_ADDR'], Request::$trusted_proxies)) {
			// Use the forwarded IP address, typically set when the
			// client is using a proxy server.
			$client_ips = \explode(',', $_SERVER['HTTP_CLIENT_IP']);

			return \array_shift($client_ips);
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			// The remote IP address
			return $_SERVER['REMOTE_ADDR'];
		}
	}

	/**
	 * @return bool
	 */
	protected static function detect_is_secure()
	{
		if (( ! empty($_SERVER['HTTPS']) AND \filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN))
			OR (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
				AND $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
			AND \in_array($_SERVER['REMOTE_ADDR'], Request::$trusted_proxies))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns the first request encountered by this framework. This will should
	 * only be set once during the first [Request::factory] invocation.
	 *
	 *     // Get the first request
	 *     $request = Request::initial();
	 *
	 *     // Test whether the current request is the first request
	 *     if (Request::initial() === Request::current())
	 *          // Do something useful
	 *
	 * @return  Request
	 * @since   3.1.0
	 */
	public static function initial()
	{
		return Request::$initial;
	}

	/**
	 * Returns information about the initial user agent.
	 *
	 * @param   mixed   $value  array or string to return: browser, version, robot, mobile, platform
	 * @return  mixed   requested information, FALSE if nothing is found
	 * @uses    Request::$user_agent
	 * @uses    Text::user_agent
	 */
	public static function user_agent($value)
	{
		return Text::user_agent(Request::$user_agent, $value);
	}

	/**
	 * Returns the accepted content types. If a specific type is defined,
	 * the quality of that type will be returned.
	 *
	 *     $types = Request::accept_type();
	 *
	 * [!!] Deprecated in favor of using [HTTP_Header::accepts_at_quality].
	 *
	 * @deprecated  since version 3.3.0
	 * @param   string  $type Content MIME type
	 * @return  mixed   An array of all types or a specific type as a string
	 * @uses    Request::_parse_accept
	 */
	public static function accept_type($type = NULL)
	{
		static $accepts;

		if ($accepts === NULL)
		{
			// Parse the HTTP_ACCEPT header
			$accepts = Request::_parse_accept($_SERVER['HTTP_ACCEPT'], array('*/*' => 1.0));
		}

		if (isset($type))
		{
			// Return the quality setting for this type
			return isset($accepts[$type]) ? $accepts[$type] : $accepts['*/*'];
		}

		return $accepts;
	}

	/**
	 * Returns the accepted languages. If a specific language is defined,
	 * the quality of that language will be returned. If the language is not
	 * accepted, FALSE will be returned.
	 *
	 *     $langs = Request::accept_lang();
	 *
	 * [!!] Deprecated in favor of using [HTTP_Header::accepts_language_at_quality].
	 *
	 * @deprecated  since version 3.3.0
	 * @param   string  $lang  Language code
	 * @return  mixed   An array of all types or a specific type as a string
	 * @uses    Request::_parse_accept
	 */
	public static function accept_lang($lang = NULL)
	{
		static $accepts;

		if ($accepts === NULL)
		{
			// Parse the HTTP_ACCEPT_LANGUAGE header
			$accepts = Request::_parse_accept($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		}

		if (isset($lang))
		{
			// Return the quality setting for this lang
			return isset($accepts[$lang]) ? $accepts[$lang] : FALSE;
		}

		return $accepts;
	}

	/**
	 * Returns the accepted encodings. If a specific encoding is defined,
	 * the quality of that encoding will be returned. If the encoding is not
	 * accepted, FALSE will be returned.
	 *
	 *     $encodings = Request::accept_encoding();
	 *
	 * [!!] Deprecated in favor of using [HTTP_Header::accepts_encoding_at_quality].
	 *
	 * @deprecated  since version 3.3.0
	 * @param   string  $type Encoding type
	 * @return  mixed   An array of all types or a specific type as a string
	 * @uses    Request::_parse_accept
	 */
	public static function accept_encoding($type = NULL)
	{
		static $accepts;

		if ($accepts === NULL)
		{
			// Parse the HTTP_ACCEPT_LANGUAGE header
			$accepts = Request::_parse_accept($_SERVER['HTTP_ACCEPT_ENCODING']);
		}

		if (isset($type))
		{
			// Return the quality setting for this type
			return isset($accepts[$type]) ? $accepts[$type] : FALSE;
		}

		return $accepts;
	}

	/**
	 * Determines if a file larger than the post_max_size has been uploaded. PHP
	 * does not handle this situation gracefully on its own, so this method
	 * helps to solve that problem.
	 *
	 * @return  boolean
	 * @uses    Num::bytes
	 * @uses    Arr::get
	 */
	public static function post_max_size_exceeded()
	{
		// Make sure the request method is POST
		if (Request::$initial->method() !== HTTP_Request::POST)
			return FALSE;

		// Get the post_max_size in bytes
		$max_bytes = Num::bytes(\ini_get('post_max_size'));

		// Error occurred if method is POST, and content length is too long
		return (Arr::get($_SERVER, 'CONTENT_LENGTH') > $max_bytes);
	}

	/**
	 * Parses an accept header and returns an array (type => quality) of the
	 * accepted types, ordered by quality.
	 *
	 *     $accept = Request::_parse_accept($header, $defaults);
	 *
	 * @param   string   $header   Header to parse
	 * @param   array    $accepts  Default values
	 * @return  array
	 */
	protected static function _parse_accept( & $header, array $accepts = NULL)
	{
		if ( ! empty($header))
		{
			// Get all of the types
			$types = \explode(',', $header);

			foreach ($types as $type)
			{
				// Split the type into parts
				$parts = \explode(';', $type);

				// Make the type only the MIME
				$type = \trim(\array_shift($parts));

				// Default quality is 1.0
				$quality = 1.0;

				foreach ($parts as $part)
				{
					// Prevent undefined $value notice below
					if (\strpos($part, '=') === FALSE)
						continue;

					// Separate the key and value
					list ($key, $value) = \explode('=', \trim($part));

					if ($key === 'q')
					{
						// There is a quality for this type
						$quality = (float) \trim($value);
					}
				}

				// Add the accept type and quality
				$accepts[$type] = $quality;
			}
		}

		// Make sure that accepts is an array
		$accepts = (array) $accepts;

		// Order by quality
		\arsort($accepts);

		return $accepts;
	}

	/**
	 * @var  string  the x-requested-with header which most likely
	 *               will be xmlhttprequest
	 */
	protected $_requested_with;

	/**
	 * @var  string  method: GET, POST, PUT, DELETE, HEAD, etc
	 */
	protected $_method = 'GET';

	/**
	 * @var  string  protocol: HTTP/1.1, FTP, CLI, etc
	 */
	protected $_protocol;

	/**
	 * @var  boolean
	 */
	protected $_secure = FALSE;

	/**
	 * @var  string  referring URL
	 */
	protected $_referrer;

	/**
	 * @var  Kohana_HTTP_Header  headers to sent as part of the request
	 */
	protected $_header;

	/**
	 * @var  string the body
	 */
	protected $_body;

	/**
	 * @var  string  controller directory
	 */
	protected $_directory = '';

	/**
	 * @var  string  controller to be executed
	 */
	protected $_controller;

	/**
	 * @var  string  action to be executed in the controller
	 */
	protected $_action;

	/**
	 * @var  string  the URI of the request
	 */
	protected $_uri;

	/**
	 * @var  array   parameters from the route
	 */
	protected $_params = array();

	/**
	 * @var array    query parameters
	 */
	protected $_get = array();

	/**
	 * @var array    post parameters
	 */
	protected $_post = array();

	/**
	 * @var array    cookies to send with the request
	 */
	protected $_cookies = array();

	/**
	 * @var string
	 */
	protected $_client_ip;

	/**
	 * @var string
	 */
	protected $_client_user_agent;

	/**
	 * Creates a new request : use either Request::fromGlobals or Request::with to build instances
	 *
	 * @param   string  $uri              URI of the request
	 *
	 * @return  void
	 * @throws  Request_Exception
	 */
	protected function __construct($uri)
	{
		// Initialise the header
		$this->_header = new HTTP_Header(array());

		if (\strpos($uri, '?') !== FALSE) {
			// This shouldn't be happening, the arguments should be pre-parsed to the base url and the $_GET array
			throw new \UnexpectedValueException('Cannot accept querystring arguments in \Request::$uri');
		}

		// Fail if they're trying to do old-school external request execution
		if (\strpos($uri, '://') === FALSE)
		{
			// Remove leading and trailing slashes from the URI
			$this->_uri = \trim($uri, '/');
		}
		else
		{
			throw new \RuntimeException('Cannot make external request with \Request');
		}
	}

	/**
	 * Returns the response as the string representation of a request.
	 *
	 *     echo $request;
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Gets the uri from the request.
	 *
	 * @return  string
	 */
	public function uri()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return ($this->_uri === '') ? '/' : $this->_uri;
	}

	/**
	 * Create a URL string from the current request. This is a shortcut for:
	 *
	 *     echo URL::site($this->request->uri(), $protocol);
	 *
	 * @param   mixed    $protocol  protocol string or Request object
	 * @return  string
	 * @since   3.0.7
	 * @uses    URL::site
	 */
	public function url($protocol = NULL)
	{
		// Create a URI with the current route, convert to a URL and returns
		return URL::site($this->uri(), $protocol);
	}

	/**
	 * Retrieves a value from the route parameters.
	 *
	 *     $id = $request->param('id');
	 *
	 * @param   string   $key      Key of the value
	 * @param   mixed    $default  Default value if the key is not set
	 * @return  mixed
	 */
	public function param($key = NULL, $default = NULL)
	{
		if ($key === NULL)
		{
			// Return the full array
			return $this->_params;
		}

		return isset($this->_params[$key]) ? $this->_params[$key] : $default;
	}

	/**
	 * Gets the referrer from the request.
	 *
	 * @return  string
	 */
	public function referrer()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_referrer;
	}

	/**
	 * Gets the directory for the controller.
	 *
	 * @return  string
	 */
	public function directory()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_directory;
	}

	/**
	 * Gets the controller for the matched route.
	 *
	 * @param   string   $controller  Controller to execute the action
	 */
	public function controller()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_controller;
	}

	/**
	 * Gets the action for the controller.
	 *
	 * @return string
	 */
	public function action()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_action;
	}

	/**
	 * Gets the requested with property, which should be relative to the x-requested-with pseudo
	 * header.
	 *
	 * @return  mixed
	 */
	public function requested_with()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_requested_with;
	}

	/**
	 * Returns whether this request is the initial request Kohana received.
	 * Can be used to test for sub requests.
	 *
	 *     if ( ! $request->is_initial())
	 *         // This is a sub request
	 *
	 * @return  boolean
	 */
	public function is_initial()
	{
		return ($this === Request::$initial);
	}

	/**
	 * Returns whether this is an ajax request (as used by JS frameworks)
	 *
	 * @return  boolean
	 */
	public function is_ajax()
	{
		return ($this->requested_with() === 'xmlhttprequest');
	}

	/**
	 * Gets the HTTP method. Usually GET, POST, PUT or DELETE in
	 * traditional CRUD applications.
	 *
	 * @return  mixed
	 */
	public function method()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_method;
	}

	/**
	 * Gets the HTTP protocol. If there is no current protocol set,
	 * it will use the default set in HTTP::$protocol
	 *
	 * @return  mixed
	 */
	public function protocol()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		if ($this->_protocol) {
			return $this->_protocol;
		} else {
			return $this->_protocol = HTTP::$protocol;
		}
	}

	/**
	 * Getter to the security settings for this request.
	 *
	 * @return  mixed
	 */
	public function secure()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_secure;
	}

	/**
	 * Gets or sets HTTP headers oo the request. All headers
	 * are included immediately after the HTTP protocol definition during
	 * transmission. This method provides a simple array or key/value
	 * interface to the headers.
	 *
	 * @param   mixed   $key   Key to get
	 * @return  mixed
	 */
	public function headers($key = NULL)
	{
		if (($key instanceof HTTP_Header) OR \is_array($key) OR (\func_num_args() > 1)) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		if ($key === NULL)
		{
			// Act as a getter, return all headers
			return $this->_header;
		}

		// Act as a getter, single header
		return ($this->_header->offsetExists($key)) ? $this->_header->offsetGet($key) : NULL;
	}

	/**
	 * Set and get cookies values for this request.
	 *
	 * @param   string $key    Cookie name
	 * @return  string|array
	 */
	public function cookie($key = NULL)
	{
		if (\is_array($key) OR (\func_num_args() > 1)) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		if ($key === NULL)
		{
			// Act as a getter, all cookies
			return $this->_cookies;
		}
		// Act as a getting, single cookie
		return isset($this->_cookies[$key]) ? $this->_cookies[$key] : NULL;
	}

	/**
	 * Gets the HTTP body of the request. The body is
	 * included after the header, separated by a single empty new line.
	 *
	 * @return  mixed
	 */
	public function body()
	{
		if (\func_num_args() > 0) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		return $this->_body;
	}

	/**
	 * Returns the length of the body for use with
	 * content header
	 *
	 * @return  integer
	 */
	public function content_length()
	{
		return \strlen($this->body());
	}

	/**
	 * Renders the HTTP_Interaction to a string, producing
	 *
	 *  - Protocol
	 *  - Headers
	 *  - Body
	 *
	 *  If there are variables set to the `Kohana_Request::$_post`
	 *  they will override any values set to body.
	 *
	 * @return  string
	 */
	public function render()
	{
		if ( ! $post = $this->post())
		{
			$body = $this->body();
		}
		else
		{
			$body = \http_build_query($post, NULL, '&');
			$this->body($body)
				->headers('content-type', 'application/x-www-form-urlencoded; charset='.Kohana::$charset);
		}

		// Set the content length
		$this->headers('content-length', (string) $this->content_length());

		// If Kohana expose, set the user-agent
		if (Kohana::$expose)
		{
			$this->headers('user-agent', Kohana::version());
		}

		// Prepare cookies
		if ($this->_cookies)
		{
			$cookie_string = array();

			// Parse each
			foreach ($this->_cookies as $key => $value)
			{
				$cookie_string[] = $key.'='.$value;
			}

			// Create the cookie string
			$this->_header['cookie'] = \implode('; ', $cookie_string);
		}

		$output = $this->method().' '.$this->uri().' '.$this->protocol()."\r\n";
		$output .= (string) $this->_header;
		$output .= $body;

		return $output;
	}

	/**
	 * Gets HTTP query string.
	 * @param   string $key	Key to get
	 * @return  mixed
	 *
	 * @uses    Arr::path
	 */
	public function query($key = NULL)
	{
		if (\is_array($key) OR (\func_num_args() > 1)) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		if ($key === NULL) {
			// Act as a getter, all query strings
			return $this->_get;
		}

		// Act as a getter, single query string
		return Arr::path($this->_get, $key);
	}

	/**
	 * Gets HTTP POST parameters to the request.
	 *
	 * @param   string $key    Key to get
	 * @return  mixed
	 * @uses    Arr::path
	 */
	public function post($key = NULL)
	{
		if (\is_array($key) OR (\func_num_args() > 1)) {
			throw new BadMethodCallException(__METHOD__.' is immutable');
		}

		if ($key === NULL)
		{
			// Act as a getter, all fields
			return $this->_post;
		}

		// Act as a getter, single field
		return Arr::path($this->_post, $key);
	}

	public function client_ip()
	{
		return $this->_client_ip;
	}

	public function client_user_agent()
	{
		return $this->_client_user_agent;
	}

	/**
	 * Gets trimmed HTTP POST parameters, useful in the common case where you don't want to accept
	 * leading / trailing whitespace in user input
	 *
	 * @return  mixed
	 */
	public function trimmedPost()
	{
		return Arr::map('trim', $this->post());
	}

	/**
	 * Assign the parameters matched by the routing layer for this request execution
	 *
	 * @param array $params
	 * @internal
	 */
	public function set_matched_route_params(array $params)
	{
		$this->_directory  = \Arr::get($params, 'directory');
		$this->_controller = $params['controller'];
		$this->_action     = $params['action'];

		unset($params['controller'], $params['action'], $params['directory']);
		$this->_params = $params;
	}

}
