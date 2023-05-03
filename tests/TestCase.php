<?php

namespace Nevadskiy\Tree\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nevadskiy\Tree\TreeServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use DatabaseTransactions;

    /**
     * @inheritdoc
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations/' .config('database.default'));
    }

    /**
     * @inheritdoc
     */
    protected function getPackageProviders($app): array
    {
        return [
            TreeServiceProvider::class,
        ];
    }
}
