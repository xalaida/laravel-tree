<?php

namespace Nevadskiy\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     */
    protected static function bootAsTree(): void
    {
        static::registerModelEvent($event = static::assignPathOnEvent(), static function (self $model) use ($event) {
            $model->assignPathIfMissing();

            if ($event === 'created') {
                $model->saveQuietly(['timestamps' => false]);
            }
        });

        static::updating(static function (self $model) {
            if ($model->shouldDetectCircularReference()) {
                $model->detectCircularReference();
            }
        });

        static::updated(static function (self $model) {
            if ($model->shouldUpdatePathOfSubtree()) {
                $model->updatePathOfSubtree();
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
     * Get the materialized path's column name.
     */
    public function getPathColumn(): string
    {
        return 'path';
    }

    /**
     * Get the materialized path of the model.
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
        return new Ancestors($this->newQuery(), $this);
    }

    /**
     * Get the model's descendants.
     */
    public function descendants(): Descendants
    {
        return new Descendants($this->newQuery(), $this);
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
    public function scopeWhereIsRoot(Builder $query): void
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
     */
    public function scopeWhereDepth(Builder $query, int $depth): void
    {
        $query->whereRaw(sprintf('nlevel(%s) = ?', $this->getPathColumn()), [$depth]);
    }

    /**
     * Order models by the depth level.
     */
    protected function scopeOrderByDepth(Builder $query, string $direction = 'asc'): void
    {
        $query->orderBy(new Expression(sprintf('nlevel(%s)', $this->getPathColumn())), $direction);
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
     * Get the event when to assign the model's path.
     */
    protected static function assignPathOnEvent(): string
    {
        $model = new static;

        if ($model->getIncrementing() && $model->getPathSourceColumn() === $model->getKeyName()) {
            return 'created';
        }

        return 'creating';
    }

    /**
     * Assign the model's path to the model if it is missing.
     */
    public function assignPathIfMissing(): void
    {
        if (is_null($this->getAttribute($this->getPathColumn()))) {
            $this->assignPath();
        }
    }

    /**
     * Assign the model's path to the model.
     */
    public function assignPath(): void
    {
        $this->setAttribute($this->getPathColumn(), $this->buildPath());
    }

    /**
     * Build the current path of the model.
     */
    protected function buildPath(): Path
    {
        if ($this->parent) {
            return Path::concat($this->parent->getPath(), $this->getPathSource());
        }

        return Path::concat($this->getPathSource());
    }

    /**
     * Determine if the node is moving when the model is not saved.
     */
    public function isMoving(): bool
    {
        return $this->isDirty($this->getParentKeyName());
    }

    /**
     * Determine if the node was moved when the model was last saved.
     */
    public function wasMoved(): bool
    {
        return $this->wasChanged($this->getParentKeyName());
    }

    /**
     * Determine whether the path of the node's subtree should be updated.
     */
    protected function shouldUpdatePathOfSubtree(): bool
    {
        return $this->wasMoved();
    }

    /**
     * Update the path of the node's subtree.
     */
    protected function updatePathOfSubtree(): void
    {
        $this->newQuery()->whereDescendantOf($this)->update([
            $this->getPathColumn() => $this->parent
                ? new Expression(vsprintf("'%s' || subpath(%s, %d)", [
                    $this->parent->getPath()->getValue(),
                    $this->getPathColumn(),
                    $this->getPath()->getDepth() - 1,
                ]))
                : new Expression(vsprintf('subpath(%s, %d)', [
                    $this->getPathColumn(), 1,
                ]))
        ]);
    }

    /**
     * Determine whether a circular reference should be detected on the node.
     */
    protected function shouldDetectCircularReference(): bool
    {
        return $this->isMoving();
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
        if (! $this->parent) {
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
