<?php

namespace Nevadskiy\Tree\ValueObjects;

use Illuminate\Support\Collection;
use Nevadskiy\Tree\SegmentTransformer\UuidPostgresLtreeSegmentTransformer;

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
     * @param string|Path ...$segments
     */
    public static function from(...$segments): Path
    {
        return new static(
            collect($segments)
                ->map(function ($segment) {
                    if ($segment instanceof Path) {
                        return $segment->getValue();
                    }

                    foreach (static::segmentTransformer() as $transformer) {
                        $segment = $transformer->set($segment);
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
                foreach (static::segmentTransformer() as $transformer) {
                    $segment = $transformer->get($segment);
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
     * Convert the path into path set of ancestors including self.
     *
     * @example ["1", "1.2", "1.2.3", "1.2.3.4"]
     */
    public function getPathSet(): array
    {
        $paths = [];

        $parts = $this->explode();

        for ($index = 0, $length = count($parts); $index < $length; $index++) {
            $paths[] = implode(self::SEPARATOR, array_slice($parts, 0, $index));
        }

        return $paths;
    }

    /**
     * Get string representation of the object.
     */
    public function __toString(): string
    {
        return $this->getValue();
    }

    /**
     * The segment transformer list.
     *
     * @todo use only for postgres.
     * @todo extract into compilePostgresPath() function.
     */
    protected static function segmentTransformer(): array
    {
        return [
            new UuidPostgresLtreeSegmentTransformer()
        ];
    }
}
