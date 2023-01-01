<?php

namespace Nevadskiy\Tree\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nevadskiy\Tree\AsTree;

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

//
// @todo finish other methods
//
//    /**
//     * Add the constraints for a relationship query.
//     *
//     * @param  \Illuminate\Database\Eloquent\Builder  $query
//     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
//     * @param  array|mixed  $columns
//     * @return \Illuminate\Database\Eloquent\Builder
//     */
//    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
//    {
//        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
//            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
//        }
//
//        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
//    }
//
//    /**
//     * Add the constraints for a relationship query on the same table.
//     *
//     * @param  \Illuminate\Database\Eloquent\Builder  $query
//     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
//     * @param  array|mixed  $columns
//     * @return \Illuminate\Database\Eloquent\Builder
//     */
//    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
//    {
//        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());
//
//        $query->getModel()->setTable($hash);
//
//        return $query->select($columns)->whereColumn(
//            $this->getQualifiedParentKeyName(), '=', $hash.'.'.$this->getForeignKeyName()
//        );
//    }
//
//    /**
//     * Get the key for comparing against the parent key in "has" query.
//     *
//     * @return string
//     */
//    public function getExistenceCompareKey()
//    {
//        return $this->getQualifiedForeignKeyName();
//    }
}
