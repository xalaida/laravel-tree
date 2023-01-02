<?php

namespace Nevadskiy\Tree\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Nevadskiy\Tree\Casts\AsPath;

class HasManyDeep extends HasMany
{
    /**
     * Get the hashed path column.
     */
    protected const HASH_PATH_COLUMN = 'laravel_reserved_path';

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
            $this->query->join($this->parent->getTable(), function (JoinClause $join) {
                $join->on($this->getQualifiedForeignKeyName(), $this->getQualifiedParentKeyName());
            });

            $this->query->whereDescendantOf($this->parent);
        }
    }

    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $models): void
    {
        $this->query->join($this->parent->getTable(), function (JoinClause $join) {
            $join->on($this->getQualifiedForeignKeyName(), $this->getQualifiedParentKeyName());
        });

        $this->query->where(function (Builder $query) use ($models) {
            foreach ($models as $model) {
                $query->orWhereDescendantOf($model);
            }
        });

        if (! $this->query->getQuery()->columns) {
            $this->query->select($this->related->qualifyColumn('*'));
        }

        $this->query->addSelect([
            sprintf("{$this->parent->getTable()}.{$this->parent->getPathColumn()} as %s", self::HASH_PATH_COLUMN)
        ]);

        $this->query->withCasts([
            self::HASH_PATH_COLUMN => AsPath::class,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function match(array $models, Collection $results, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $results->filter(function (Model $result) use ($model) {
                return $result->getAttribute(self::HASH_PATH_COLUMN)->segments()->contains($model->getPathSource())
                    && $result->isNot($model);
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
}
