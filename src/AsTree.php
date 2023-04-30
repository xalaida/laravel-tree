<?php

namespace Nevadskiy\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Expression;
use Nevadskiy\Tree\Casts\AsPath;
use Nevadskiy\Tree\Collections\NodeCollection;
use Nevadskiy\Tree\Exceptions\CircularReferenceException;
use Nevadskiy\Tree\Relations\Ancestors;
use Nevadskiy\Tree\Relations\Descendants;
use Nevadskiy\Tree\ValueObjects\Path;

/**
 * @mixin Model
 */
trait AsTree
{
    /**
     * Boot the trait.
     *
     * @todo extract into observer.
     */
    protected static function bootAsTree(): void
    {
        static::registerModelEvent($event = static::assignPathOnEvent(), static function (self $model) use ($event) {
            if ($model->shouldAssignPath()) {
                $model->assignPath();

                if ($event === 'created' && $model->hasPath()) {
                    $model->newQuery()->whereKey($model->getKey())->toBase()->update([
                        $model->getPathColumn() => $model->getPath()->getValue(),
                    ]);
                }
            }
        });

        static::updating(static function (self $model) {
            if ($model->shouldDetectCircularReference()) {
                $model->detectCircularReference();
            }
        });

        static::updated(static function (self $model) {
            if ($model->shouldRebuildSubtreePath()) {
                $model->rebuildSubtreePath();
            }
        });
    }

    /**
     * Initialize the trait.
     */
    protected function initializeAsTree(): void
    {
        $this->mergeCasts([
            $this->getPathColumn() => AsPath::class,
        ]);
    }

    /**
     * Get the path's column name.
     */
    public function getPathColumn(): string
    {
        return 'path';
    }

    /**
     * Get the path of the model.
     */
    public function getPath(): Path
    {
        return $this->getAttribute($this->getPathColumn());
    }

    /**
     * Get the source column name of the model's path.
     */
    public function getPathSourceColumn(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the source value of the model's path.
     */
    public function getPathSource(): string
    {
        return $this->getAttribute($this->getPathSourceColumn());
    }

    /**
     * Get the parent key name.
     */
    public function getParentKeyName(): string
    {
        return 'parent_id';
    }

    /**
     * Get a relation with a parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, $this->getParentKeyName());
    }

    /**
     * Get a relation with children categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, $this->getParentKeyName());
    }

    /**
     * Get the model's ancestors.
     */
    public function ancestors(): Ancestors
    {
        return Ancestors::of($this);
    }

    /**
     * Get the model's descendants.
     */
    public function descendants(): Descendants
    {
        return Descendants::of($this);
    }

    /**
     * @inheritdoc
     */
    public function newCollection(array $models = []): NodeCollection
    {
        return new NodeCollection($models);
    }

    /**
     * Get the root items.
     */
    public function scopeWhereRoot(Builder $query): void
    {
        $query->whereNull($this->getParentKeyName());
    }

    /**
     * Determine if it is the root node.
     */
    public function isRoot(): bool
    {
        return is_null($this->getAttribute($this->getParentKeyName()));
    }

    /**
     * Get items by the given depth level.
     *
     * @todo check performance on both mysql and pgsql connections.
     */
    public function scopeWhereDepth(Builder $query, int $depth, string $operator = '='): void
    {
        if ($this->getConnection() instanceof PostgresConnection) {
            $query->whereRaw(sprintf('nlevel(%s) %s ?', $this->getPathColumn(), $operator), [$depth]);
        } else if ($this->getConnection() instanceof MySqlConnection) {
            $query->whereRaw(vsprintf("(length(%s) - length(replace(%s, '%s', ''))) + 1 %s ?", [
                $this->getPathColumn(),
                $this->getPathColumn(),
                '.', // separator
                $operator,
            ]), [$depth]);
        }
    }

    /**
     * Order models by the depth level.
     *
     * @todo check performance on both mysql and pgsql connections.
     */
    protected function scopeOrderByDepth(Builder $query, string $direction = 'asc'): void
    {
        if ($this->getConnection() instanceof PostgresConnection) {
            $query->orderBy(new Expression(sprintf('nlevel(%s)', $this->getPathColumn())), $direction);
        } else if ($this->getConnection() instanceof MySqlConnection) {
            $query->orderBy(new Expression(vsprintf("(length(%s) - length(replace(%s, '%s', ''))) + 1", [
                $this->getPathColumn(),
                $this->getPathColumn(),
                '.' // separator
            ])), $direction);
        }
    }

    /**
     * Order models by the depth level.
     */
    protected function scopeOrderByDepthDesc(Builder $query): void
    {
        $query->orderByDepth('desc');
    }

    /**
     * Join the ancestors of the model.
     */
    public function joinAncestors(): NodeCollection
    {
        return $this->ancestors->sortByDepthDesc()->prepend($this);
    }

    /**
     * Determine if the current node is an ancestor of the given node.
     */
    public function isAncestorOf(self $that): bool
    {
        return $this->getPath()->segments()->contains($that->getPathSource())
            && ! $this->is($that);
    }

    /**
     * Determine if the current node is a descendant of the given node.
     */
    public function isDescendantOf(self $that): bool
    {
        return $that->isAncestorOf($this);
    }

    /**
     * Get the event when to assign the model's path.
     */
    protected static function assignPathOnEvent(): string
    {
        if (static::assignPathDuringInsert()) {
            return 'creating';
        }

        return 'created';
    }

    /**
     * Determine whether the path should be assigned during insert.
     */
    protected static function assignPathDuringInsert(): bool
    {
        $model = new static();

        if ($model->getIncrementing() && $model->getPathSourceColumn() === $model->getKeyName()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the path attribute should be assigned.
     */
    protected function shouldAssignPath(): bool
    {
        return ! $this->hasPath();
    }

    /**
     * Assign the model's path to the model.
     */
    public function assignPath(): void
    {
        $this->setAttribute($this->getPathColumn(), $this->buildPath());
    }

    /**
     * Determine whether the model has the path attribute.
     */
    public function hasPath(): bool
    {
        return ! is_null($this->getAttribute($this->getPathColumn()));
    }

    /**
     * Build the current path of the model.
     */
    protected function buildPath(): Path
    {
        if ($this->isRoot()) {
            return Path::concat($this->getPathSource());
        }

        return Path::concat($this->parent->getPath(), $this->getPathSource());
    }

    /**
     * Determine if the parent node is changing when the model is not saved.
     */
    public function isParentChanging(): bool
    {
        return $this->isDirty($this->getParentKeyName());
    }

    /**
     * Determine if the parent node is changed when the model is saved.
     */
    public function isParentChanged(): bool
    {
        return $this->wasChanged($this->getParentKeyName());
    }

    /**
     * Determine whether the path of the node's subtree should be rebuilt.
     */
    protected function shouldRebuildSubtreePath(): bool
    {
        return $this->isParentChanged();
    }

    /**
     * Rebuild the path of the node's subtree.
     */
    protected function rebuildSubtreePath(): void
    {
        if ($this->getConnection() instanceof PostgresConnection) {
            $this->newQuery()->whereSelfOrDescendantOf($this)->update([
                $this->getPathColumn() => $this->isRoot()
                    ? new Expression(vsprintf('subpath(%s, %d)', [
                        $this->getPathColumn(), 1,
                    ]))
                    : new Expression(vsprintf("'%s' || subpath(%s, %d)", [
                        $this->parent->getPath()->getValue(),
                        $this->getPathColumn(),
                        $this->getPath()->getDepth() - 1,
                    ]))
            ]);
        }
    }

    /**
     * Determine whether a circular reference should be detected on the node.
     */
    protected function shouldDetectCircularReference(): bool
    {
        return $this->isParentChanging();
    }

    /**
     * Detect a circular reference on the node.
     */
    protected function detectCircularReference(): void
    {
        if ($this->hasCircularReference()) {
            $this->onCircularReferenceDetected();
        }
    }

    /**
     * Determine whether the node has a circular reference.
     */
    protected function hasCircularReference(): bool
    {
        if ($this->isRoot()) {
            return false;
        }

        return $this->parent->getPath()->segments()->contains($this->getPathSource());
    }

    /**
     * Throw the circular reference exception.
     */
    protected function onCircularReferenceDetected(): void
    {
        throw new CircularReferenceException($this);
    }
}
