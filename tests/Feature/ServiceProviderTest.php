<?php

use Illuminate\Routing\Router;
use MilliPress\AcornMilliCache\AcornMilliCacheServiceProvider;
use MilliPress\AcornMilliCache\Http\Middleware\StoreResponse;

it('merges default config when registered', function () {
    $provider = new AcornMilliCacheServiceProvider($this->app);
    $provider->register();

    expect(config('millicache.middleware.enabled'))->toBeTrue();
    expect(config('millicache.middleware.groups'))->toBe(['web']);
    expect(config('millicache.cacheable_status_codes'))->toBe([200]);
});

it('has publishable config', function () {
    $provider = new AcornMilliCacheServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $paths = AcornMilliCacheServiceProvider::pathsToPublish(
        AcornMilliCacheServiceProvider::class,
        'millicache'
    );

    expect($paths)->not->toBeEmpty();

    $joined = implode('|', array_values($paths));
    expect($joined)->toContain('millicache.php');
});

it('pushes middleware to configured groups when enabled', function () {
    $provider = new AcornMilliCacheServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    /** @var Router $router */
    $router = $this->app['router'];
    $groups = $router->getMiddlewareGroups();

    expect($groups['web'] ?? [])->toContain(StoreResponse::class);
});

it('does not push middleware when disabled', function () {
    config()->set('millicache.middleware.enabled', false);

    $provider = new AcornMilliCacheServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    /** @var Router $router */
    $router = $this->app['router'];
    $groups = $router->getMiddlewareGroups();

    expect($groups['web'] ?? [])->not->toContain(StoreResponse::class);
});

it('respects custom middleware groups', function () {
    config()->set('millicache.middleware.groups', ['web', 'api']);

    $provider = new AcornMilliCacheServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    /** @var Router $router */
    $router = $this->app['router'];
    $groups = $router->getMiddlewareGroups();

    expect($groups['web'] ?? [])->toContain(StoreResponse::class);
    expect($groups['api'] ?? [])->toContain(StoreResponse::class);
});
