<?php

namespace Nevadskiy\Tree\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Database\BuilderMixin;

/**
 * @property AsTree related
 */
class Descendants extends Relation
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
                $this->query->whereSelfOrDescendantOf($this->related);
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
                $query->orWhereSelfOrDescendantOf($model);
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
                return $model->isDescendantOf($result);
            }));
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    public function getResults(): Collection
    {
        return $this->query->get();
    }

    /**
     * @inheritdoc
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        $query->select($columns);

        $query->from("{$query->getModel()->getTable()} as descendants");

        $query->whereColumn(
            "descendants.{$this->related->getPathColumn()}",
            BuilderMixin::DESCENDANT,
            $this->related->qualifyColumn($this->related->getPathColumn())
        );

        $query->whereColumn(
            "descendants.{$this->related->getKeyName()}",
            '!=',
            $this->related->getQualifiedKeyName()
        );

        return $query;
    }
}
