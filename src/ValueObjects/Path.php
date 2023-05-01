<?php

namespace Nevadskiy\Tree\ValueObjects;

use Illuminate\Support\Collection;
use Nevadskiy\Tree\SegmentProcessors\UuidPostgresLtreeSegmentProcessor;

class Path
{
    /**
     * The path's separator.
     */
    public const SEPARATOR = '.';

    /**
     * The path's value.
     *
     * @var string
     */
    private $value;

    /**
     * Build a path from the given segments.
     *
     * @todo rename to `from` which makes more sense when using with single argument.
     * @param string|Path ...$segments
     */
    public static function concat(...$segments): Path
    {
        return new static(
            collect($segments)
                ->map(function ($segment) {
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
    public function segments(): Collection
    {
        return collect($this->explode())
            ->map(function (string $segment) {
                foreach (static::segmentProcessors() as $processor) {
                    $segment = $processor->get($segment);
                }

                return $segment;
            });
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
     * Get string representation of the object.
     */
    public function __toString(): string
    {
        return $this->getValue();
    }

    /**
     * The segment processor list.
     *
     * @todo use only for postgres.
     * @todo extract into compilePostgresPath() function.
     */
    protected static function segmentProcessors(): array
    {
        return [
            new UuidPostgresLtreeSegmentProcessor()
        ];
    }
}
