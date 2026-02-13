<?php

namespace MilliCache\Acorn\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * We intentionally do NOT load the full ServiceProvider
     * because it calls pushMiddlewareToGroup() which requires the full
     * router stack. Individual tests register only the components they need.
     */
    protected function getPackageProviders($app): array
    {
        return [];
    }

    /**
     * Work around Acorn's HandleExceptions::flushState() calling
     * PHPUnit\Runner\ErrorHandler::enable() without the required
     * TestCase argument (incompatible with PHPUnit 12).
     */
    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } catch (\ArgumentCountError $e) {
            if (str_contains($e->getMessage(), 'ErrorHandler::enable()')) {
                return;
            }
            throw $e;
        }
    }
}
