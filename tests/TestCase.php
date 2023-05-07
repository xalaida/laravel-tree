<?php

namespace Nevadskiy\Tree\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nevadskiy\Tree\TreeServiceProvider;
use Orchestra\Testbench\Database\MigrateProcessor;
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
        $this->loadMigrationsWithoutRollbackFrom(__DIR__ . '/Database/migrations/' . config('database.default'));
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

    /**
     * Define hooks to migrate the database before each test without rollback after.
     */
    private function loadMigrationsWithoutRollbackFrom($paths): void
    {
        $migrator = new MigrateProcessor($this, $this->resolvePackageMigrationsOptions($paths));
        $migrator->up();

        $this->resetApplicationArtisanCommands($this->app);
    }

}
