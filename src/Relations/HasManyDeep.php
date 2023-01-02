<?php

namespace Nevadskiy\Tree\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;

class HasManyDeep extends HasMany
{
    /**
     * Make a new class instance.
     */
    public static function between(Model $parent, string $related, string $foreignKey = null, string $localKey = null): HasManyDeep
    {
        $relatedInstance = self::newRelatedInstance($related, $parent);

        return new static(
            $relatedInstance->newQuery(),
            $parent,
            $foreignKey ?: $relatedInstance->qualifyColumn($parent->getForeignKey()),
            $localKey ?: $relatedInstance->getKeyName()
        );
    }

    /**
     * Create a new model instance for a related model.
     */
    protected static function newRelatedInstance(string $class, Model $parent)
    {
        return tap(new $class, static function ($related) use ($parent) {
            if (! $related->getConnectionName()) {
                $related->setConnection($parent->getConnectionName());
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->join($this->parent->getTable(), function (JoinClause $join) {
                $join->on($this->getQualifiedForeignKeyName(), $this->getQualifiedParentKeyName());
            });

            $query->whereDescendantOf($this->parent);

            $query->whereNotNull($this->foreignKey);
        }
    }
}
