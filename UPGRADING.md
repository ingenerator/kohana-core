# Upgrading from upstream kohana/core 3.3

If you use Kohana how we do, most of this will be a drop-in replacement that just 
removes features / options that aren't in use. There are a few breaking changes

## 4.2.x series

### Update your minion module path if required

If you're using kohana/minion it's now provided with this package : change your bootstrap
to point to vendor/ingenerator/kohana-core/modules/minion.

## 4.0.x series

### Disable encrypted sessions

If you're using encrypted sessions you'll need to switch that off before you switch
to this package. You may want to run a deploy with your existing codebase to silently
decrypt them over a period of 24-48 hours before switching them off. Otherwise all users
will get bumped out of their sessions when you ship this.

### Stop using for Feed parsing and external requests

Check your code for Request::factory() or Feed:: class references. Switch all this code
over to guzzle or similar.

### Update your index.php to use the new request initialisaion, execution and exception handling code

This whole code path has changed. Requests are no longer globally stateful by default, they know nothing
about executing themselves, and they don't catch any exceptions.

Instead of the default:

```php
require __DIR__.'/../application/bootstrap.php';
echo Request::factory(TRUE, [], FALSE, [])
  ->execute()
  ->send_headers()
  ->body();
```

You'll want:
```php
try {
  require __DIR__.'/../application/bootstrap.php';
  $executor = new Request_Executor(Route::all());
  echo $executor->execute(Request::initInitial(Request::fromGlobals()))
    ->send_headers()
    ->body();
} catch (HTTP_Exception $e) {
  echo $e->response()->send_headers()->body();
} catch (Exception $e) {
  // Or if you've made it to PHP7, catch Throwable $e
  echo Kohana_Exception::_handler($e)->response()->send_headers()->body();
}
```

You can of course use an alternate exception handling block, but bear in mind this will now
also catch exceptions thrown from bootstrapping so there is no guarantee you'll have access
to any of the kohana classes, services, constants or anything else you're expecting. Be 
defensive.


### Rewrite any HMVC code

I don't have any good suggestions for this, but we no longer support executing a request
inside another request. It might work if you create a new Request_Executor, but not if you
need to be able to access \Request::current() or \Request::$current as these are both gone.
If you only need access to the currently executing request from a controller that should 
probably work and you can always access \Request::initial(). But you're on your own with this.
Use a real microservice, or make the nested interactor / view / whatever calls in your app 
directly.

### [Optional] Remove $request, $response from controller constructors

Controllers no longer take request and response objects directly in their constructors, 
instead they receive them in setRequestContext. This allows for creating controllers in e.g.
a service container or a unit test. They're still passed by default though, so you can
leave in constructors that use them for now. 
