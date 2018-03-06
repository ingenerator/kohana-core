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

### Update your index.php to use the new request initialisaion code

Instead of the default:

```php
echo Request::factory(TRUE, [], FALSE, [])
  ->execute()
  ->send_headers()
  ->body();
```

You'll want:
```php
echo Request::initInitial(Request::fromGlobals())
  ->execute()
  ->send_headers()
  ->body();
```
