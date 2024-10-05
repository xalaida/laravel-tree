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
    protected $loadEnvironmentVariables = true;

    /**
     * @inheritdoc
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsWithoutRollbackFrom(__DIR__ . '/Database/migrations/' . env('DB_CONNECTION'));
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
