<?php

namespace Nevadskiy\Tree\ValueObjects;

use Nevadskiy\Tree\SegmentProcessors\UuidPostgresLtreeSegmentProcessor;
use Stringable;

class Path implements Stringable
{
    /**
     * The path's separator.
     */
    protected const SEPARATOR = '.';

    /**
     * The path's value.
     */
    private string $value;

    /**
     * Build a path from the given segments.
     */
    public static function concat(Path|string ...$segments): Path
    {
        return new static(
            collect($segments)
                ->map(function (Path|string $segment) {
                    if ($segment instanceof Path) {
                        return $segment->getValue();
                    }

                    foreach (static::segmentProcessors() as $processor) {
                        $segment = $processor->set($segment);
                    }

                    return $segment;
                })
                ->implode(self::SEPARATOR)
        );
    }

    /**
     * Make a new path instance.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Get the path's value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get segments of the path.
     */
    public function segments(): array
    {
        return collect($this->explode())
            ->map(function (string $segment) {
                foreach (static::segmentProcessors() as $processor) {
                    $segment = $processor->get($segment);
                }

                return $segment;
            })
            ->all();
    }

    /**
     * Get the path ancestor segments.
     */
    public function ancestors(): array
    {
        $segments = $this->segments();

        array_pop($segments);

        return $segments;
    }

    /**
     * Get the depth level of the path.
     */
    public function getDepth(): int
    {
        return count($this->explode());
    }

    /**
     * Explode a path to segments.
     */
    protected function explode(): array
    {
        return explode(self::SEPARATOR, $this->getValue());
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->getValue();
    }

    /**
     * The segment processor list.
     *
     * @todo consider setting this to static prop from service provider when app has configured at least single postgres database connection.
     */
    protected static function segmentProcessors(): array
    {
        return [
            new UuidPostgresLtreeSegmentProcessor()
        ];
    }
}
