---
title: 'Configuration'
post_excerpt: 'Config reference for middleware registration and automatic cache clearing.'
menu_order: 10
---

# Configuration

The published config file lives at `config/millicache.php` in your Acorn application. It controls the `StoreResponse` middleware and automatic cache clearing for Artisan commands — all other caching settings (TTL, grace period, exclusions, compression, etc.) are managed by [MilliCache itself](https://millipress.com/docs/millicache/configuration/).

## Config Reference

```php
return [

    'middleware' => [
        'enabled' => true,
        'groups' => ['web'],
    ],

    'clear' => [
        'optimize:clear' => 'route*',
        'route:clear'    => 'route*',
        'route:cache'    => 'route*',
    ],

];
```

| Key                    | Type                       | Default     | Description                                             |
|------------------------|----------------------------|-------------|---------------------------------------------------------|
| `middleware.enabled`   | `bool`                     | `true`      | Whether to auto-register the `StoreResponse` middleware |
| `middleware.groups`    | `list<string>`             | `['web']`   | Router middleware groups the middleware is appended to   |
| `clear`                | `array<string, string>`    | *(see above)* | Maps Artisan commands to flag patterns for automatic cache clearing |

## Adding Middleware Groups

By default, the middleware is only added to the `web` group. If you have Acorn routes in other middleware groups that should be cached, add them to the `groups` array:

```php
'middleware' => [
    'enabled' => true,
    'groups' => ['web', 'api'],
],
```

The middleware is appended to each group via `pushMiddlewareToGroup()`, so it runs *after* all other middleware in the group — exactly when the response is ready to be captured.

## Disabling Automatic Registration

If you need full control over where the middleware runs, disable auto-registration and register it manually:

```php
// config/millicache.php
'middleware' => [
    'enabled' => false,
    'groups' => ['web'],
],
```

Then register the middleware yourself in a service provider or route file:

```php
use MilliCache\Acorn\Http\Middleware\StoreResponse;

// In a route group
Route::middleware([StoreResponse::class])->group(function () {
    Route::get('/cached-route', [MyController::class, 'index']);
});

// Or append to a group manually
$router->pushMiddlewareToGroup('web', StoreResponse::class);
```

> [!TIP]
> Manual registration is useful when you want the middleware on specific routes rather than an entire group, or when you need to control its position in the middleware stack.

## Automatic Cache Clearing

The `clear` config maps Artisan commands to MilliCache flag patterns. When a listed command runs, all cache entries matching its flag pattern are automatically cleared.

```php
'clear' => [
    'optimize:clear' => 'route*',
    'route:clear'    => 'route*',
    'route:cache'    => 'route*',
],
```

The key is the Artisan command name, the value is the flag pattern to clear:

| Pattern | Clears |
|---------|--------|
| `route*` | All Acorn route caches (named and unnamed) |
| `route:products:index` | Only the `products.index` route cache |
| `route:api*` | All API route caches |
| `*` | All MilliCache entries (including WordPress page caches) |

You can add your own commands to the list:

```php
'clear' => [
    'optimize:clear' => 'route*',
    'route:clear'    => 'route*',
    'route:cache'    => 'route*',
    'deploy:finish'  => 'route*',     // custom deployment command
],
```

To disable automatic clearing entirely, set `clear` to an empty array:

```php
'clear' => [],
```

## Related Configuration

All other caching behavior is configured through MilliCache and MilliRules:

- **TTL, grace period, compression** — [MilliCache Configuration](https://millipress.com/docs/millicache/configuration/)
- **Cache exclusions, conditions, rules** — [MilliRules Documentation](https://millipress.com/docs/millirules/)
- **Route-aware conditions** — [Acorn MilliRules Documentation](https://millipress.com/docs/acorn-millirules/)
