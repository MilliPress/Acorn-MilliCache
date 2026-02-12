<?php

/**
 * PHPStan bootstrap stubs for Laravel/Acorn helper functions.
 *
 * These functions are provided at runtime by Acorn (roots/acorn)
 * but PHPStan cannot discover them without Larastan.
 */

if (! function_exists('config')) {
    /**
     * @param  array<string, mixed>|string|null  $key
     * @param  mixed  $default
     * @return ($key is null ? \Illuminate\Config\Repository : ($key is string ? mixed : null))
     */
    function config($key = null, $default = null)
    {
    }
}

if (! function_exists('millicache')) {
    /**
     * @return \MilliCache\Engine
     */
    function millicache()
    {
    }
}
