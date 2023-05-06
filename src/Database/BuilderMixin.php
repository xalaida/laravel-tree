<?php

namespace Nevadskiy\Tree\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Nevadskiy\Tree\ValueObjects\Path;
use RuntimeException;

/**
 * @mixin Builder
 */
class BuilderMixin
{
    /**
     * The descendant SQL operator.
     */
    public const ANCESTOR = '@>';

    /**
     * The descendant SQL operator.
     */
    public const DESCENDANT = '<@';

    /**
     * Add an ancestor where clause to the query.
     */
    public function whereSelfOrAncestor(): callable
    {
        return function (string $column, Path $path, string $boolean = 'and') {
            if ($this->getConnection() instanceof MySqlConnection) {
                return $this->whereIn($column, $path->getPathSet(), $boolean);
            }

            if ($this->getConnection() instanceof PostgresConnection) {
                return $this->where($column, BuilderMixin::ANCESTOR, $path, $boolean);
            }

            throw new RuntimeException(vsprintf('Database connection [%s] is not supported.', [
                get_class($this->getConnection())
            ]));
        };
    }

    /**
     * Add an ancestor where column clause to the query.
     */
    public function whereColumnSelfOrAncestor(): callable
    {
        return function (string $first, string $second, string $boolean = 'and') {
            if ($this->getConnection() instanceof MySqlConnection) {
                return $this->whereRaw(sprintf('find_in_set(%s, path_to_ancestor_set(%s))', $first, $second), [], $boolean);
            }

            if ($this->getConnection() instanceof PostgresConnection) {
                return $this->whereColumn($first, BuilderMixin::ANCESTOR, $second, $boolean);
            }

            throw new RuntimeException(vsprintf('Database connection [%s] is not supported.', [
                get_class($this->getConnection())
            ]));
        };
    }

    /**
     * Add an ancestor "or where" clause to the query.
     */
    public function orWhereSelfOrAncestor(): callable
    {
        return function (string $column, Path $path) {
            return $this->whereSelfOrAncestor($column, $path, 'or');
        };
    }

    /**
     * Add an ancestor where clause to the query from the given model.
     */
    public function whereSelfOrAncestorOf(): callable
    {
        return function (Model $model, string $column = null, string $boolean = 'and') {
            return $this->whereSelfOrAncestor(
                $column ?: $model->newQuery()->qualifyColumn($model->getPathColumn()),
                $model->getPath(),
                $boolean
            );
        };
    }

    /**
     * Add an ancestor "or where" clause to the query from the given model.
     */
    public function orWhereSelfOrAncestorOf(): callable
    {
        return function (Model $model, string $column = null) {
            return $this->orWhereSelfOrAncestor(
                $column ?: $model->newQuery()->qualifyColumn($model->getPathColumn()),
                $model->getPath(),
            );
        };
    }

    /**
     * Add a descendant where clause to the query.
     */
    public function whereSelfOrDescendant(): callable
    {
        return function (string $column, Path $path, string $boolean = 'and') {
            if ($this->getConnection() instanceof MySqlConnection) {
                return $this->where($column, 'like', "{$path}%", $boolean);
            }

            if ($this->getConnection() instanceof PostgresConnection) {
                return $this->where($column, BuilderMixin::DESCENDANT, $path, $boolean);
            }

            throw new RuntimeException(vsprintf('Database connection [%s] is not supported.', [
                get_class($this->getConnection())
            ]));
        };
    }

    /**
     * Add a descendant where column clause to the query.
     */
    public function whereColumnSelfOrDescendant(): callable
    {
        return function (string $first, string $second, string $boolean = 'and') {
            if ($this->getConnection() instanceof MySqlConnection) {
                return $this->whereColumn($first, 'like', new Expression("concat({$second}, '%')"), $boolean);
            }
            if ($this->getConnection() instanceof PostgresConnection) {
                return $this->whereColumn($first, BuilderMixin::DESCENDANT, $second, $boolean);
            }

            throw new RuntimeException(vsprintf('Database connection [%s] is not supported.', [
                get_class($this->getConnection())
            ]));
        };
    }

    /**
     * Add a descendant "or where" clause to the query.
     */
    public function orWhereSelfOrDescendant(): callable
    {
        return function (string $column, Path $path) {
            return $this->whereSelfOrDescendant($column, $path, 'or');
        };
    }

    /**
     * Add a descendant where clause to the query from the given model.
     */
    public function whereSelfOrDescendantOf(): callable
    {
        return function (Model $model, string $column = null, string $boolean = 'and') {
            return $this->whereSelfOrDescendant(
                $column ?: $model->newQuery()->qualifyColumn($model->getPathColumn()),
                $model->getPath(),
                $boolean
            );
        };
    }

    /**
     * Add a descendant "or where" clause to the query from the given model.
     */
    public function orWhereSelfOrDescendantOf(): callable
    {
        return function (Model $model, string $column = null) {
            return $this->orWhereSelfOrDescendant(
                $column ?: $model->newQuery()->qualifyColumn($model->getPathColumn()),
                $model->getPath(),
            );
        };
    }

    /**
     * Filter records by the given depth level.
     */
    public function wherePathDepth(): callable
    {
        return function (string $column, int $depth, string $operator = '=') {
            if ($this->getConnection() instanceof PostgresConnection) {
                $this->where($this->compilePgsqlDepth($column), $operator, $depth);
            } else if ($this->getConnection() instanceof MySqlConnection) {
                $this->where($this->compileMysqlDepth($column), $operator, $depth);
            }
        };
    }

    /**
     * Compile the PostgreSQL "depth" function for the given column.
     */
    protected function compilePgsqlDepth(): callable
    {
        return function (string $column) {
            return new Expression(sprintf('nlevel(%s)', $column));
        };
    }

    /**
     * Compile the MySQL "depth" function for the given column.
     */
    protected function compileMysqlDepth(): callable
    {
        return function (string $column, string $separator = Path::SEPARATOR) {
            return new Expression(vsprintf("(length(%s) - length(replace(%s, '%s', ''))) + 1", [
                $column, $column, $separator
            ]));
        };
    }
}
