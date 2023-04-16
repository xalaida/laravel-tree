<?php

namespace Nevadskiy\Tree\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use RuntimeException;

/**
 * @mixin \Illuminate\Database\Query\Builder
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
        return function (string $column, string $path, string $boolean = 'and') {
            if ($this->getConnection() instanceof MySqlConnection) {
                return $this->where($column, 'like', $path, $boolean); // @todo perform whereIn on each segment.
            } if ($this->getConnection() instanceof PostgresConnection) {
                return $this->where($column, BuilderMixin::ANCESTOR, $path, $boolean);
            }

            throw new RuntimeException();
        };
    }

    /**
     * Add an ancestor "or where" clause to the query.
     */
    public function orWhereSelfOrAncestor(): callable
    {
        return function (string $column, string $path) {
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
        return function (string $column, string $path, string $boolean = 'and') {
            if ($this->getConnection() instanceof MySqlConnection) {
                return $this->where($column, 'like', "{$path}%", $boolean);
            } if ($this->getConnection() instanceof PostgresConnection) {
                return $this->where($column, BuilderMixin::DESCENDANT, $path, $boolean);
            }

            throw new RuntimeException();
        };
    }

    /**
     * Add a descendant "or where" clause to the query.
     */
    public function orWhereSelfOrDescendant(): callable
    {
        return function (string $column, string $path) {
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
}
