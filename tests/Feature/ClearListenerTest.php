<?php

use Illuminate\Console\Events\CommandFinished;
use MilliCache\Acorn\ServiceProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

require_once __DIR__ . '/../Support/MilliCacheMock.php';

beforeEach(function () {
    MilliCacheMock::$instance = new MilliCacheMock();
});

function fireCommandFinished(object $app, string $command, int $exitCode = 0): void
{
    $app['events']->dispatch(new CommandFinished(
        $command,
        new ArrayInput([]),
        new NullOutput(),
        $exitCode,
    ));
}

it('clears cache when a configured command finishes successfully', function () {
    config()->set('millicache.clear', [
        'optimize:clear' => 'route*',
    ]);

    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    fireCommandFinished($this->app, 'optimize:clear');

    expect(MilliCacheMock::$instance->clearedPatterns)->toBe(['route*']);
    expect(MilliCacheMock::$instance->executeQueueCalled)->toBeTrue();
});

it('does not clear cache when command exits with non-zero code', function () {
    config()->set('millicache.clear', [
        'optimize:clear' => 'route*',
    ]);

    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    fireCommandFinished($this->app, 'optimize:clear', exitCode: 1);

    expect(MilliCacheMock::$instance->clearedPatterns)->toBeEmpty();
    expect(MilliCacheMock::$instance->executeQueueCalled)->toBeFalse();
});

it('does not clear cache for unconfigured commands', function () {
    config()->set('millicache.clear', [
        'optimize:clear' => 'route*',
    ]);

    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    fireCommandFinished($this->app, 'cache:clear');

    expect(MilliCacheMock::$instance->clearedPatterns)->toBeEmpty();
    expect(MilliCacheMock::$instance->executeQueueCalled)->toBeFalse();
});

it('uses the flag pattern from config for each command', function () {
    config()->set('millicache.clear', [
        'optimize:clear' => 'route*',
        'route:clear' => 'route:api*',
    ]);

    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    fireCommandFinished($this->app, 'route:clear');

    expect(MilliCacheMock::$instance->clearedPatterns)->toBe(['route:api*']);
});

it('does not register listener when clear config is empty', function () {
    config()->set('millicache.clear', []);

    $provider = new ServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    fireCommandFinished($this->app, 'optimize:clear');

    expect(MilliCacheMock::$instance->clearedPatterns)->toBeEmpty();
});
