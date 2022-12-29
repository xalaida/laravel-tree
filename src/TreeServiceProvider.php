<?php

namespace Nevadskiy\Tree;

use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Nevadskiy\Tree\Database\BuilderMixin;
use RuntimeException;

/**
 * @todo add "mysql" support based on "LIKE" operator (consider specifying grammar compiler in config).
 */
class TreeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerBuilderMixin();
        $this->registerLtreeType();
        $this->registerLtreeColumn();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootMigrations();
    }

    /**
     * Register the ltree type for database.
     */
    private function registerLtreeType(): void
    {
        Grammar::macro('typeLtree', function () {
            // @todo refactor with closure bind $this->get('db')->connection();
            $driver = DB::connection()->getDriverName();

            if ($driver === 'pgsql') {
                return 'ltree';
            }

            throw new RuntimeException(sprintf('Driver "%s" does not support "Ltree" extension.', $driver));
        });
    }

    /**
     * Register the ltree column on the blueprint.
     */
    private function registerLtreeColumn(): void
    {
        Blueprint::macro('ltree', function (string $name) {
            return $this->addColumn('ltree', $name);
        });
    }

    /**
     * Register the query builder mixin.
     */
    private function registerBuilderMixin(): void
    {
        Builder::mixin(new BuilderMixin());
    }

    /**
     * Boot any package migrations.
     */
    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
