<?php

namespace Nevadskiy\Tree\Collections;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NodeCollection extends Collection
{
    /**
     * Get a node collection as a tree.
     */
    public function tree(): NodeCollection
    {
        $this->link();

        $depth = $this->min(function (Model $node) {
            return $node->getPath()->getDepth();
        });

        return $this->filter(function (Model $node) use ($depth) {
            return $node->getPath()->getDepth() === $depth;
        });
    }

    /**
     * Link nodes using the "children" relation.
     */
    public function link(): NodeCollection
    {
        $parents = $this->groupBy($this->first()->getParentKeyName());

        $this->each(function (Model $node) use ($parents) {
            $node->setRelation('children', $parents->get($node->getKey(), new static()));
        });

        return $this;
    }

    /**
     * Get root nodes of the collection.
     */
    public function root(): NodeCollection
    {
        return $this->filter(function (Model $node) {
            return $node->isRoot();
        });
    }

    /**
     * Sort the collection by a depth level.
     */
    public function sortByDepth(bool $descending = false): NodeCollection
    {
        return $this->sortBy(function (Model $node) {
            return $node->getPath()->getDepth();
        }, SORT_REGULAR, $descending);
    }

    /**
     * Sort the collection in descending order by a depth level.
     */
    public function sortByDepthDesc(): NodeCollection
    {
        return $this->sortByDepth(true);
    }
}
