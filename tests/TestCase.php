<?php

namespace Nevadskiy\Tree\Tests;

use Nevadskiy\Tree\TreeServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Support/Migrations');

        $this->artisan('migrate')->run();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders(): array
    {
        return [
            TreeServiceProvider::class,
        ];
    }
}
