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
class Descendants extends Relation
{
    /**
     * @inheritdoc
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->query->where(function () {
                $this->query->whereDescendantOf($this->related);
                $this->query->whereKeyNot($this->related->getKey());
            });
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     */
    public function addEagerConstraints(array $models): void
    {
        $this->getRelationQuery()
             ->where(function (Builder $query) use ($models) {
                 foreach ($models as $model) {
                     $query->orWhereDescendantOf($model);
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
                 return $result->getPath()->segments()->contains($model->getPathSource())
                     && $model->isNot($result);
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

        $subQueryTable = $this->getRelationCountHash();

        $query->from("{$query->getModel()->getTable()} as {$subQueryTable}");

        $query->whereColumn(
            "{$subQueryTable}.{$this->related->getPathColumn()}",
            BuilderMixin::DESCENDANT,
            $this->related->qualifyColumn($this->related->getPathColumn())
        );

        $query->whereColumn(
            "{$subQueryTable}.{$this->related->getKeyName()}",
            '!=',
            $this->related->getQualifiedKeyName()
        );

        return $query;
    }
}
