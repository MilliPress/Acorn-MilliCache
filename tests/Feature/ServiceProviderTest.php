<?php

use Illuminate\Routing\Router;
use MilliCache\Acorn\ServiceProvider;
use MilliCache\Acorn\Http\Middleware\StoreResponse;

it('merges default config when registered', function () {
    $provider = new ServiceProvider($this->app);
    $provider->register();

    expect(config('millicache.middleware.enabled'))->toBeTrue();
    expect(config('millicache.middleware.groups'))->toBe(['web']);
});

it('has publishable config', function () {
    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $paths = ServiceProvider::pathsToPublish(
        ServiceProvider::class,
        'millicache'
    );

    expect($paths)->not->toBeEmpty();

    $joined = implode('|', array_values($paths));
    expect($joined)->toContain('millicache.php');
});

it('pushes middleware to configured groups when enabled', function () {
    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    /** @var Router $router */
    $router = $this->app['router'];
    $groups = $router->getMiddlewareGroups();

    expect($groups['web'] ?? [])->toContain(StoreResponse::class);
});

it('does not push middleware when disabled', function () {
    config()->set('millicache.middleware.enabled', false);

    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    /** @var Router $router */
    $router = $this->app['router'];
    $groups = $router->getMiddlewareGroups();

    expect($groups['web'] ?? [])->not->toContain(StoreResponse::class);
});

it('respects custom middleware groups', function () {
    config()->set('millicache.middleware.groups', ['web', 'api']);

    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    /** @var Router $router */
    $router = $this->app['router'];
    $groups = $router->getMiddlewareGroups();

    expect($groups['web'] ?? [])->toContain(StoreResponse::class);
    expect($groups['api'] ?? [])->toContain(StoreResponse::class);
});
