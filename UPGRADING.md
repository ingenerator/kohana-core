# Upgrading from upstream kohana/core 3.3

If you use Kohana how we do, most of this will be a drop-in replacement that just 
removes features / options that aren't in use. There are a few breaking changes

## 4.0.x series

### Disable encrypted sessions

If you're using encrypted sessions you'll need to switch that off before you switch
to this package. You may want to run a deploy with your existing codebase to silently
decrypt them over a period of 24-48 hours before switching them off. Otherwise all users
will get bumped out of their sessions when you ship this.

### Stop using for Feed parsing and external requests

Check your code for Request::factory() or Feed:: class references. Switch all this code
over to guzzle or similar.

### Update your index.php to use the new request initialisaion and execution code

Instead of the default:

```php
echo Request::factory(TRUE, [], FALSE, [])
  ->execute()
  ->send_headers()
  ->body();
```

You'll want:
```php
$executor = new Request_Executor(Route::all());
echo $executor->execute(Request::initInitial(Request::fromGlobals()))
  ->send_headers()
  ->body();
```

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
