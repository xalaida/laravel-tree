<?php

namespace Nevadskiy\Tree\SegmentTransformer;

interface SegmentTransformer
{
    /**
     * Transform the path segment before saving to database.
     */
    public function set(string $segment): string;

    /**
     * Restore the path segment after retrieving from database.
     */
    public function get(string $segment): string;
}
