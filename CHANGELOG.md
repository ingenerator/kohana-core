You're really going to want to read this.

## Unreleased

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
