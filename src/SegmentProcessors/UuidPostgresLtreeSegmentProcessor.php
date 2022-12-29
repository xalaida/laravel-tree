<?php

namespace Nevadskiy\Tree\SegmentProcessors;

class UuidPostgresLtreeSegmentProcessor implements SegmentProcessor
{
    /**
     * Replace dashes to match requirements of the Postgres Ltree extension.
     */
    public function set(string $segment): string
    {
        return str_replace('-', '_', $segment);
    }

    /**
     * @inheritdoc
     */
    public function get(string $segment): string
    {
        return str_replace('_', '-', $segment);
    }
}
