<?php

use Illuminate\Support\Facades\Route;
use MilliCache\Acorn\Http\Middleware\StoreResponse;

require_once __DIR__ . '/../Support/MilliCacheMock.php';

beforeEach(function () {
    MilliCacheMock::$instance = new MilliCacheMock();
});

it('adds route flag with dots converted to colons for named routes', function () {
    Route::middleware(StoreResponse::class)
        ->get('/test/products', fn () => 'OK')
        ->name('products.index');

    $this->get('/test/products');

    expect(MilliCacheMock::$instance->addedFlags)->toBe(['route:products:index']);
});

it('adds bare route flag for unnamed routes', function () {
    Route::middleware(StoreResponse::class)
        ->get('/test/unnamed', fn () => 'unnamed response');

    $this->get('/test/unnamed');

    expect(MilliCacheMock::$instance->addedFlags)->toBe(['route']);
});

it('converts deeply nested route names to colons', function () {
    Route::middleware(StoreResponse::class)
        ->get('/test/api/users', fn () => 'OK')
        ->name('api.v1.users.index');

    $this->get('/test/api/users');

    expect(MilliCacheMock::$instance->addedFlags)->toBe(['route:api:v1:users:index']);
});

it('skips flagging and storing when caching is not allowed', function () {
    MilliCacheMock::$instance->cachingAllowed = false;

    Route::middleware(StoreResponse::class)
        ->get('/test/no-cache', fn () => 'OK');

    $this->get('/test/no-cache');

    expect(MilliCacheMock::$instance->addedFlags)->toBeEmpty();
    expect(MilliCacheMock::$instance->storeCalled)->toBe(0);
});

it('returns response unchanged when store throws', function () {
    MilliCacheMock::$instance->storeThrows = true;

    Route::middleware(StoreResponse::class)
        ->get('/test/failing', fn () => 'expected content');

    $response = $this->get('/test/failing');

    $response->assertOk();
    $response->assertSee('expected content');
});

it('skips storing when caching is disallowed during next', function () {
    Route::middleware(StoreResponse::class)
        ->get('/test/post-check', function () {
            // Simulate ExecuteRules calling do_cache(false) inside $next()
            MilliCacheMock::$instance->cachingAllowed = false;

            return 'should not be cached';
        });

    $response = $this->get('/test/post-check');

    $response->assertOk();
    $response->assertSee('should not be cached');
    expect(MilliCacheMock::$instance->storeCalled)->toBe(0);
    expect(MilliCacheMock::$instance->addedFlags)->toBeEmpty();
});

it('stores response content and headers', function () {
    Route::middleware(StoreResponse::class)
        ->get('/test/store', fn () => response('cached body', 200, ['X-Custom' => 'value']));

    $this->get('/test/store');

    expect(MilliCacheMock::$instance->storeCalled)->toBe(1);
});
