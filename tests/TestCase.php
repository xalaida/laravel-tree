<?php

namespace Nevadskiy\Tree\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nevadskiy\Tree\TreeServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Support/migrations');
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
