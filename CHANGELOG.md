You're really going to want to read this.

## Unreleased

* Show the parent chain of exceptions in the dev_html_error page template.
  Assist with debugging by summarising any exceptions that have been linked as
  the parents of the exception that was finally caught. 
* Handle Throwable as well as Exception when reading session data. PHP handles
  problems in custom handlers inconsistently - some methods bubble exceptions,
  others are caught and converted to an `Error` with a distinct method of its
  own and the original exception in the `$previous` property. With this update
  all session read errors / exceptions are converted to a Session_Exception.
* Accept any Throwable as `$previous` for all Kohana exceptions

## v4.5.0 (2020-02-14)

* Include previous exception in Session_Exception when reading the session fails:
  can be retrieved for logging / debugging with
  `} catch (SessionException $e) { $cause = $e->getPrevious(); }`
* [NB] Changes the default HTML error view to one that does not render environment, method
  call params and other internal details. The stack trace is still shown to aid debugging,
  and it's possible that exception messages will show sensitive information, but this
  significantly reduces the default exposure when custom error handling goes wrong or has
  not yet been initialised. The old kohana error dump view is enabled in the
  `Kohana::DEVELOPMENT` environment for local debugging. As with the cli error you can
   customise the template by passing `[error_view => 'name_of_view']` to Kohana::init().

## v4.4.0 (2020-01-30)

* [NB] Now defines a default cli_error view if the PHP_SAPI==='cli', rather than the default
  html view that comes with kohana. You can override this by passing 
  `[error_view => 'name_of_view']` to Kohana::init().
* [*BREAKING*] Cookies now default to secure and http-only.
* Allow setting custom options (path, domain, secure etc) for an individual cookie.
* Deprecate Cookie::$salt, ::$secure, etc in favour of a combined Cookie::configure() method
  to set multiple options all at once e.g. from bootstrap. 

## v4.3.3 (2020-01-30)

* [NB] Do not throw any exception on requests containing a ? in the URL, treat it as literal.
  If a user-agent has sent a request containing an encoded `?` character, this is correctly
  treated by the webserver as an escaped literal character that forms part of the URL path /
  filename, not a querystring separator. In this case the `?` appears in PATH_INFO and the
  app should likewise treat it as a valid URL containing a `?`. This, of course, may not
  match any defined route but that is for the routing layer to handle and 404 as required.
  The exception was primarily added by us to catch legacy behaviour e.g. unit tests that
  were creating a request object with a `?a=b` in the URL and relying on that being parsed
  as a querystring in the returned object. This warning/failure is no longer available.
* Handle potentially empty (null) file argument for Kohana_Upload::not_empty (fixes 
  TypeError from the explicit array typehint on that method.

## v4.3.2 (2019-12-10)

* Update list of Response status codes to IANA list as at 2018-09-01
  https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml

## v4.3.1 (2019-11-07)

* Fix some errors in migrating unit test mocking to PHPUnit 7
* Do not throw an exception on requests to invalid URLS like //index.php?stuff=query

## v4.3.0 (2019-04-02)

* Drop support for PHP < 7.2
* Fixup tests for PHPUnit 7
* Skip some tests in kohana form (we're barely using it anyway and don't plan to continue)
* Replace use of each() to reach PHP 7.2 compatibility

## v4.2.3 (2019-04-02)

* Bundle kohana/minion as an optional module with this package.

## v4.2.2 (2019-04-01)

* Explicitly use namespaced calls for all global functions for performance 
  https://veewee.github.io/blog/optimizing-php-performance-by-fq-function-calls/

## v4.2.1 (2018-05-31)

* Support hyphenated task names in minion e.g. do:some-complex-thing becomes Task_Db_SomeComplexThing
* Add Session_Array driver for use in contexts where you don't want to persist session data, for example
  from CLI scripts or unit tests. Generally in your bootstrap you'd do something like:
  ```php
  if (PHP_SAPI === 'cli') {
    Session::$default = 'array';
  }
  ```

## v4.2.0 (2018-03-14)

* Fix the Minion_Exception::handler method signature to be compatible with updated Kohana_Exception::handler
* Bundle kohana/minion as an optional module with this package.

## v4.1.0 (2018-03-13)

* [BREAKING] Remove all exception handling from Request_Executor : you will need to re-implement this
  in index.php 

## 4.0.0 (2018-03-07)

* Use setter injection rather than constructor injection for passing request / response to
  controllers.
* Make Controller->request and Controller->response protected, not public
* Remove \Request::$current and \Request::current() - HMVC no longer in use
* Remove all support for external routes - these are no longer relevant now we don't support
  external requests.
* Extract all request execution from the \Request class - instead, use Request_Executor to execute
  the request from outside.
* Remove Request_Client and all internal references in Request
* Only show a single backtrace line in the HTML error view within PHPUnit
  - otherwise stack traces are still able to be gigabytes in size and to
  run out of memory.
* Allow use of HTTP 422 Unprocessable Entity response code
* Add \Request::trimmedPost for easy access to POST data with all whitespace trimmed
* Inform composer that this package replaces and conflicts with kohana/core upstream
* Relax typehint for error handlers to support PHP7 exceptions / throwables
* Refactor Request_Client_Internal to support extension of code that loads and executes controllers.
  Note: now calls `->execute()` directly : no longer uses ReflectionMethod->invoke() - may cause a fatal
  if you somehow route a request to a class that is not a controller. 
* Add support for loading controllers with namespaced classes per https://github.com/kohana/core/pull/578/files
  thanks to @rjd22 for the original implementation.
* Removed default support for passing client params to Request_Client
* Add new Request::initInitial which should now be called from index.php when creating the
  main request. Request::fromGlobals no longer modifies any global state.
* Disable Request::factory, replace with Request::fromGlobals (to init all data from $_SERVER etc)
  and Request::with (to init from a predefined set of values). Request::fromGlobals no longer
  modifies the Request::$initial singleton : instead, you should set this separately.
* Remove option to pass allow_external, client_params and injected_routes to \Request classes
  - temporarily refactored test to assign the _routes protected property directly in the one
  place that this is necessary.
* Made all \Request properties idempotent : will now throw if you attempt to use the getters
  as setters.
* Removed HTTP_Message interface to entirely split the Request and
  Response interfaces - this will allow refactoring Request to be
  idempotent instead of exposing setters.
* Removed all the Request_Client caching, header callbacks (auth / redirects etc) - these were 
  mostly relevant for external requests and perhaps a little bit for HMVC which is also on the
  way to the door.
* Removed all support for making external requests with Kohana : use Guzzle or similar - allows
  significant simplification of request handling.
* Removed Feed parser - as it depends on using Kohana's external request client which is about
  to be dropped. 
* Removed Encrypt:: implementation as it's no longer secure. Also removes encrypted sessions - 
  Session::instance will throw if you have them configured. 
* Renamed to ingenerator/kohana-core

## v3.3.6 (2016-07-25)

* The final official Kohana release
