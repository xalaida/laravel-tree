<?php

namespace Nevadskiy\Tree\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Nevadskiy\Tree\Casts\AsPath;
use Nevadskiy\Tree\Database\BuilderMixin;

class HasManyDeep extends HasMany
{
    /**
     * Get the hashed path column.
     */
    protected const HASH_PATH_COLUMN = 'laravel_reserved_path';

    /**
     * Make a new class instance.
     */
    public static function between(
        Model $parent,
        string $related,
        string $foreignKey = null,
        string $localKey = null
    ): self {
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
        return tap(new $class(), static function ($related) use ($parent) {
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
        if (static::$constraints && $this->getParentKey()) {
            $this->joinParent();

            $this->query->whereSelfOrDescendantOf($this->parent);

            $this->query->select($this->related->qualifyColumn('*'));
        }
    }

    /**
     * Join the parent's model table.
     */
    protected function joinParent(): void
    {
        $this->query->join($this->parent->getTable(), function (JoinClause $join) {
            $join->on($this->getQualifiedForeignKeyName(), $this->getQualifiedParentKeyName());
        });
    }

    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $models): void
    {
        $this->joinParent();

        $this->query->where(function (Builder $query) use ($models) {
            foreach ($models as $model) {
                $query->orWhereSelfOrDescendantOf($model);
            }
        });
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     */
    public function get($columns = ['*']): Collection
    {
        if ($columns === ['*']) {
            $columns = ["{$this->related->getTable()}.*"];
        }

        $columns = array_merge($columns, [
            sprintf("{$this->parent->getTable()}.{$this->parent->getPathColumn()} as %s", self::HASH_PATH_COLUMN)
        ]);

        $this->query->withCasts([
            self::HASH_PATH_COLUMN => AsPath::class,
        ]);

        return $this->query->get($columns);
    }

    /**
     * @inheritdoc
     */
    public function match(array $models, Collection $results, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $results->filter(function (Model $result) use ($model) {
                return $result->getAttribute(self::HASH_PATH_COLUMN)->segments()->contains($model->getPathSource());
            }));
        }

        return $models;
    }

    /**
     * @inheritdoc
     * @todo cover all cases with tests.
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        $query->select($columns);

        $hash = $this->getRelationCountHash();

        $query->join("{$this->parent->getTable()} as {$hash}", function (JoinClause $join) use ($hash) {
            $join->on($this->getForeignKeyName(), "{$hash}.{$this->getLocalKeyName()}");
        });

        $query->whereColumnSelfOrAncestor(
            $this->parent->qualifyColumn($this->parent->getPathColumn()),
            "{$hash}.{$this->parent->getPathColumn()}"
        );

        // @todo probably exclude "self".

        return $query;
    }
}
