<?php

namespace Nevadskiy\Tree\Tests\Mysql;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nevadskiy\Tree\TreeServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class MysqlTestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    /**
     * @inheritDoc
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.host', 'mysql');
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
