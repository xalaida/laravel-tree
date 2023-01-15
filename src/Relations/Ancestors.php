<?php

namespace Nevadskiy\Tree\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Database\BuilderMixin;

/**
 * @property AsTree $related
 */
class Ancestors extends Relation
{
    /**
     * Make a new relation instance for the given model.
     */
    public static function of(Model $model): self
    {
        return new static($model->newQuery(), $model);
    }

    /**
     * @inheritdoc
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->query->where(function () {
                $this->query->whereSelfOrAncestorOf($this->related);
                $this->query->whereKeyNot($this->related->getKey());
            });
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     */
    public function addEagerConstraints(array $models): void
    {
        $this->query->where(function (Builder $query) use ($models) {
            foreach ($models as $model) {
                $query->orWhereSelfOrAncestorOf($model);
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    public function match(array $models, Collection $results, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $results->filter(function (Model $result) use ($model) {
                return $model->isAncestorOf($result);
            }));
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    public function getResults(): Collection
    {
        return ! is_null($this->related->isRoot())
            ? $this->query->get()
            : $this->related->newCollection();
    }

    /**
     * @inheritdoc
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        $query->select($columns);

        $query->from("{$query->getModel()->getTable()} as ancestors");

        $query->whereColumn(
            "ancestors.{$this->related->getPathColumn()}",
            BuilderMixin::ANCESTOR,
            $this->related->qualifyColumn($this->related->getPathColumn())
        );

        $query->whereColumn(
            "ancestors.{$this->related->getKeyName()}",
            '!=',
            $this->related->getQualifiedKeyName()
        );

        return $query;
    }
}
