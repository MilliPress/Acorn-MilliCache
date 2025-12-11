<?php

namespace MilliPress\AcornMilliCache\Providers;

use Illuminate\Support\ServiceProvider;
use MilliCache\Engine;

/**
 * MilliCache Service Provider for Acorn
 *
 * Integrates MilliCache with Roots Acorn and Bedrock projects.
 *
 * @package MilliPress\AcornMilliCache
 */
class MilliCacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * This method is called first and is used to bind services into the container.
     *
     * @return void
     */
    public function register(): void
    {
        // Define MilliCache constants if not already defined
        $this->defineConstants();

        // Merge package config with published config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/millicache.php',
            'millicache'
        );

        // Bind MilliCache Engine as singleton in the service container
        $this->app->singleton('millicache', function ($app) {
            return Engine::instance();
        });
    }

    /**
     * Bootstrap services.
     *
     * This method is called after all service providers have been registered.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file to user's config directory
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/millicache.php' => $this->app->configPath('millicache.php'),
            ], 'millicache-config');
        }

        // Initialize MilliCache for WordPress
        if (function_exists('add_action')) {
            $this->initializeMilliCache();
        }
    }

    /**
     * Define MilliCache constants if not already defined.
     *
     * In Bedrock/Composer installations, the plugin may not be traditionally
     * activated, so we need to ensure constants are defined.
     *
     * @return void
     */
    protected function defineConstants(): void
    {
        if (!defined('MILLICACHE_VERSION')) {
            // Get version from composer.lock or set default
            $version = $this->getMilliCacheVersion();
            define('MILLICACHE_VERSION', $version);
        }

        if (!defined('MILLICACHE_FILE')) {
            $millicachePath = $this->getMilliCachePath();
            define('MILLICACHE_FILE', $millicachePath . '/millicache.php');
            define('MILLICACHE_DIR', $millicachePath);
        }

        if (!defined('MILLICACHE_BASENAME')) {
            define('MILLICACHE_BASENAME', plugin_basename(MILLICACHE_FILE));
        }
    }

    /**
     * Initialize MilliCache plugin.
     *
     * Loads the MilliCache plugin and handles first-run activation tasks.
     *
     * @return void
     */
    protected function initializeMilliCache(): void
    {
        // Hook into WordPress to load MilliCache early
        add_action('plugins_loaded', function () {
            // Check if MilliCache is already loaded (e.g., as traditional plugin)
            if (function_exists('run_millicache')) {
                return;
            }

            // Load MilliCache programmatically
            $this->loadMilliCache();

            // Handle first-run activation tasks if needed
            $this->maybeRunActivation();
        }, 5);
    }

    /**
     * Load the MilliCache plugin.
     *
     * @return void
     */
    protected function loadMilliCache(): void
    {
        $millicachePath = $this->getMilliCachePath();
        $pluginFile = $millicachePath . '/millicache.php';

        if (file_exists($pluginFile)) {
            require_once $pluginFile;
        } else {
            // Log error if MilliCache is not found
            if (function_exists('error_log')) {
                error_log('MilliCache plugin not found at: ' . $pluginFile);
            }
        }
    }

    /**
     * Get the path to the MilliCache plugin.
     *
     * @return string
     */
    protected function getMilliCachePath(): string
    {
        // In Bedrock, vendor is at project root
        return $this->app->basePath('vendor/millipress/millicache');
    }

    /**
     * Get MilliCache version from installed package.
     *
     * @return string
     */
    protected function getMilliCacheVersion(): string
    {
        $composerLock = $this->app->basePath('composer.lock');

        if (file_exists($composerLock)) {
            $lock = json_decode(file_get_contents($composerLock), true);

            foreach ($lock['packages'] ?? [] as $package) {
                if ($package['name'] === 'millipress/millicache') {
                    return $package['version'] ?? '1.0.0';
                }
            }
        }

        return '1.0.0';
    }

    /**
     * Run activation tasks on first load.
     *
     * Since Bedrock doesn't use traditional plugin activation hooks,
     * we need to run activation tasks manually on first load.
     *
     * @return void
     */
    protected function maybeRunActivation(): void
    {
        $option_name = 'millicache_acorn_activated';

        if (!get_option($option_name)) {
            // Run activation logic
            if (class_exists('MilliCache\Admin\Activator')) {
                \MilliCache\Admin\Activator::activate();
            }

            // Mark as activated
            update_option($option_name, true);
        }
    }
}
