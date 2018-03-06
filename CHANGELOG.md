You're really going to want to read this.

## Unreleased

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
