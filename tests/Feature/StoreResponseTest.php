<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use MilliCache\Acorn\Http\Middleware\StoreResponse;

require_once __DIR__ . '/../Support/MilliCacheMock.php';

beforeEach(function () {
    MilliCacheMock::$instance = new MilliCacheMock();
});

afterEach(function () {
    unset($_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING']);
    $_GET = [];
    $_REQUEST = [];
});

/**
 * Seed the superglobals the way PHP would for a real request.
 */
function seedSuperglobals(string $uri): void
{
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['QUERY_STRING'] = (string) parse_url($uri, PHP_URL_QUERY);
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    $_REQUEST = $_GET;
}

it('removes ignored query keys from the request before the controller runs', function () {
    seedSuperglobals('/test/normalize?gclid=abc&page=2&utm_source=ads');

    Route::middleware(StoreResponse::class)
        ->get('/test/normalize', fn (Request $request) => implode('|', [
            $request->fullUrl(),
            $request->getRequestUri(),
            json_encode($request->query()),
            $request->server('QUERY_STRING'),
        ]));

    $response = $this->get('/test/normalize?gclid=abc&page=2&utm_source=ads');

    $response->assertOk();
    expect(MilliCacheMock::$instance->normalizeCalled)->toBe(1);
    expect($response->getContent())->toBe(implode('|', [
        'http://localhost/test/normalize?page=2',
        '/test/normalize?page=2',
        '{"page":"2"}',
        'page=2',
    ]));
    expect(MilliCacheMock::$instance->storeCalled)->toBe(1);
});

it('keeps route parameters and the matched route after normalizing', function () {
    seedSuperglobals('/test/items/42?utm_campaign=x');

    Route::middleware(StoreResponse::class)
        ->get('/test/items/{id}', fn (Request $request, string $id) => $id.':'.$request->route()->getName())
        ->name('items.show');

    $response = $this->get('/test/items/42?utm_campaign=x');

    $response->assertOk();
    expect($response->getContent())->toBe('42:items.show');
    expect(MilliCacheMock::$instance->addedFlags)->toBe(['route:items:show']);
});

it('leaves the request untouched when caching is not allowed', function () {
    MilliCacheMock::$instance->cachingAllowed = false;
    seedSuperglobals('/test/raw?gclid=abc');

    Route::middleware(StoreResponse::class)
        ->get('/test/raw', fn (Request $request) => (string) $request->query('gclid'));

    $response = $this->get('/test/raw?gclid=abc');

    expect(MilliCacheMock::$instance->normalizeCalled)->toBe(0);
    expect($response->getContent())->toBe('abc');
    expect($_SERVER['REQUEST_URI'])->toBe('/test/raw?gclid=abc');
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
