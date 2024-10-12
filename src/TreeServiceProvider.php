<?php

namespace Nevadskiy\Tree;

use Illuminate\Database\Grammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Nevadskiy\Tree\Database\BuilderMixin;

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
        $this->publishMigrations();
        $this->registerSQLiteFunctions();
    }

    /**
     * Register the query builder mixin.
     */
    private function registerBuilderMixin(): void
    {
        Builder::mixin(new BuilderMixin());
    }

    /**
     * Register the "ltree" column type for database.
     */
    private function registerLtreeType(): void
    {
        Grammar::macro('typeLtree', function () {
            return 'ltree';
        });
    }

    /**
     * Register the "ltree" column on the blueprint.
     */
    private function registerLtreeColumn(): void
    {
        Blueprint::macro('ltree', function (string $name) {
            return $this->addColumn('ltree', $name);
        });
    }

    /**
     * Register the functions for SQLite driver.
     */
    private function registerSQLiteFunctions(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('substring_index', function ($string, $delimiter, $count) {
                if ($count > 0) {
                    return implode($delimiter, array_slice(explode($delimiter, $string), 0, $count));
                } elseif ($count < 0) {
                    return implode($delimiter, array_slice(explode($delimiter, $string), $count));
                }
                return '';
            });
        }
    }

    /**
     * Publish any package migrations.
     */
    private function publishMigrations(): void
    {
        $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'pgsql-ltree-migration');
    }
}
