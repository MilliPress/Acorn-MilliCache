<?php

namespace MilliCache\Acorn;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use MilliCache\Acorn\Http\Middleware\StoreResponse;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/millicache.php', 'millicache');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMiddleware();
        $this->registerClearListener();
    }

    /**
     * Register publishable config.
     */
    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/millicache.php' => $this->app->configPath('millicache.php'),
        ], 'millicache');
    }

    /**
     * Push the StoreResponse middleware into configured groups.
     */
    protected function registerMiddleware(): void
    {
        /** @var bool $enabled */
        $enabled = $this->app['config']->get('millicache.middleware.enabled', true);

        if (! $enabled) {
            return;
        }

        /** @var list<string> $groups */
        $groups = $this->app['config']->get('millicache.middleware.groups', ['web']);

        foreach ($groups as $group) {
            $this->app['router']->pushMiddlewareToGroup($group, StoreResponse::class);
        }
    }

    /**
     * Listen for Artisan commands that should trigger cache clearing.
     *
     * The 'clear' config maps command names to flag patterns. When a
     * configured command finishes successfully, matching cache entries
     * are cleared via MilliCache's invalidation API.
     */
    protected function registerClearListener(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        /** @var array<string, string> $commands */
        $commands = $this->app['config']->get('millicache.clear', []);

        if ($commands === []) {
            return;
        }

        $this->app['events']->listen(CommandFinished::class, function (CommandFinished $event) use ($commands): void {
            if ($event->exitCode !== 0) {
                return;
            }

            $pattern = $commands[$event->command] ?? null;

            if ($pattern === null || ! function_exists('millicache')) {
                return;
            }

            millicache()->clear()->flags($pattern)->execute_queue();
        });
    }
}
