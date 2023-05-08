<?php

namespace Nevadskiy\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Nevadskiy\Tree\Casts\AsPath;
use Nevadskiy\Tree\Collections\NodeCollection;
use Nevadskiy\Tree\Exceptions\CircularReferenceException;
use Nevadskiy\Tree\Relations\Ancestors;
use Nevadskiy\Tree\Relations\Descendants;
use Nevadskiy\Tree\ValueObjects\Path;

/**
 * @mixin Model
 * @property-read AsTree|null parent
 */
trait AsTree
{
    /**
     * Boot the trait.
     */
    protected static function bootAsTree(): void
    {
        static::registerModelEvent($event = static::assignPathOnEvent(), static function (self $model) use ($event) {
            call_user_func([$model, 'assignPathWhen'.Str::ucfirst($event)]);
        });

        static::updating(static function (self $model) {
            if ($model->shouldDetectCircularReference()) {
                $model->detectCircularReference();
            }
        });

        static::updated(static function (self $model) {
            if ($model->shouldRebuildSubtreePaths()) {
                $model->rebuildSubtreePaths();
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
     * Filter records by root.
     */
    protected function scopeWhereRoot(Builder $query): void
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
     * Filter records by the given depth level.
     */
    public function scopeWhereDepth(Builder $query, int $depth, string $operator = '='): void
    {
        $query->wherePathDepth($this->getPathColumn(), $depth, $operator);
    }

    /**
     * Order records by a depth.
     */
    protected function scopeOrderByDepth(Builder $query, string $direction = 'asc'): void
    {
        $query->orderByPathDepth($this->getPathColumn(), $direction);
    }

    /**
     * Order records by a depth descending.
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
        if (static::shouldAssignPathDuringInsert()) {
            return 'creating';
        }

        return 'created';
    }

    /**
     * Determine whether the path should be assigned during insert.
     */
    protected static function shouldAssignPathDuringInsert(): bool
    {
        $model = new static();

        if ($model->getIncrementing() && $model->getPathSourceColumn() === $model->getKeyName()) {
            return false;
        }

        return true;
    }

    /**
     * Assign a path to the model when it is created.
     */
    protected function assignPathWhenCreated(): void
    {
        if ($this->shouldAssignPath()) {
            $this->assignPath();
            $this->saveQuietly();
        }
    }

    /**
     * Assign a path to the model when it is creating.
     */
    protected function assignPathWhenCreating(): void
    {
        if ($this->shouldAssignPath()) {
            $this->assignPath();
        }
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
            return Path::from($this->getPathSource());
        }

        return Path::from($this->parent->getPath(), $this->getPathSource());
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
     * Determine whether the paths of the subtree should be rebuilt.
     */
    protected function shouldRebuildSubtreePaths(): bool
    {
        return $this->isParentChanged();
    }

    /**
     * Rebuild the paths of the subtree.
     */
    protected function rebuildSubtreePaths(): void
    {
        $this->newQuery()
            ->whereSelfOrDescendantOf($this)
            ->rebuildPaths(
                $this->getPathColumn(),
                $this->getPath(),
                $this->isRoot()
                    ? null
                    : $this->parent->getPath(),
            );
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
