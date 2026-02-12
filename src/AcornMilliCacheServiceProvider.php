<?php

namespace MilliPress\AcornMilliCache;

use Illuminate\Support\ServiceProvider;
use MilliPress\AcornMilliCache\Http\Middleware\StoreResponse;

class AcornMilliCacheServiceProvider extends ServiceProvider
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
}
