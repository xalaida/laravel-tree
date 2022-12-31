<?php

namespace Nevadskiy\Tree\Database;

use Illuminate\Database\Eloquent\Model;

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
    public function whereAncestor(): callable
    {
        return function (string $column, string $path, string $boolean = 'and') {
            return $this->where($column, BuilderMixin::ANCESTOR, $path, $boolean);
        };
    }

    /**
     * Add an ancestor "or where" clause to the query.
     */
    public function orWhereAncestor(): callable
    {
        return function (string $column, string $path) {
            return $this->whereAncestor($column, $path, 'or');
        };
    }

    /**
     * Add an ancestor where clause to the query from the given model.
     */
    public function whereAncestorOf(): callable
    {
        return function (Model $model, string $boolean = 'and') {
            return $this->whereAncestor(
                $model->newQuery()->qualifyColumn($model->getPathColumn()),
                $model->getPath(),
                $boolean
            );
        };
    }

    /**
     * Add an ancestor "or where" clause to the query from the given model.
     */
    public function orWhereAncestorOf(): callable
    {
        return function (Model $model) {
            return $this->whereAncestorOf($model, 'or');
        };
    }

    /**
     * Add a descendant where clause to the query.
     */
    public function whereDescendant(): callable
    {
        return function (string $column, string $path, string $boolean = 'and') {
            return $this->where($column, BuilderMixin::DESCENDANT, $path, $boolean);
        };
    }

    /**
     * Add a descendant "or where" clause to the query.
     */
    public function orWhereDescendant(): callable
    {
        return function (string $column, string $path) {
            return $this->whereDescendant($column, $path, 'or');
        };
    }

    /**
     * Add a descendant where clause to the query from the given model.
     */
    public function whereDescendantOf(): callable
    {
        return function (Model $model, string $boolean = 'and') {
            return $this->whereDescendant(
                $model->newQuery()->qualifyColumn($model->getPathColumn()),
                $model->getPath(),
                $boolean
            );
        };
    }

    /**
     * Add a descendant "or where" clause to the query from the given model.
     */
    public function orWhereDescendantOf(): callable
    {
        return function (Model $model) {
            return $this->whereDescendantOf($model, 'or');
        };
    }
}
