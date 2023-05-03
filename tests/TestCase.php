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
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'mysql') {
            $this->loadMigrationsFrom(__DIR__.'/Support/Migrations/Mysql');
        } else if (config('database.default') === 'pgsql') {
            $this->loadMigrationsFrom(__DIR__.'/Support/Migrations/Postgres');
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'mysql'); // @todo use dynamic connection.

        $app['config']->set('database.connections.mysql.host', 'mysql'); // @todo improve perf
        $app['config']->set('database.connections.pgsql.host', 'postgres');
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
