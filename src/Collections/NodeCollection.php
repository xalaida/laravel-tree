<?php

namespace Nevadskiy\Tree\Collections;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NodeCollection extends Collection
{
    /**
     * Sort the collection by the depth level.
     */
    public function sortByDepth(): NodeCollection
    {
        return $this->sortBy(function (Model $model) {
            return $model->getPath()->getDepth();
        });
    }

    /**
     * Sort the collection in descending order by the depth level.
     */
    public function sortByDepthDesc(): NodeCollection
    {
        return $this->sortByDesc(function (Model $model) {
            return $model->getPath()->getDepth();
        });
    }
}
