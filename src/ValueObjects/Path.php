<?php

namespace Nevadskiy\Tree\ValueObjects;

use Illuminate\Support\Collection;

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
        return collect($this->split());
    }

    /**
     * Get the depth level of the path.
     */
    public function getDepth(): int
    {
        return count($this->split());
    }

    /**
     * Split a path to segments.
     */
    protected function split(): array
    {
        return explode(self::SEPARATOR, $this->getValue());
    }

    /**
     * Split the path into a separate set of paths of ancestor nodes including self.
     *
     * @example ["1", "1.2", "1.2.3", "1.2.3.4"]
     */
    public function splitIntoSelfOrAncestors(): array
    {
        $output = [];

        $parts = $this->split();

        for ($index = 0, $length = count($parts); $index < $length; $index++) {
            $output[] = implode(self::SEPARATOR, array_slice($parts, 0, $index + 1));
        }

        return $output;
    }

    /**
     * Split the path into a separate path set of paths of ancestor nodes.
     *
     * @example ["1", "1.2", "1.2.3"]
     */
    public function splitIntoAncestors(): array
    {
        $output = $this->splitIntoSelfOrAncestors();

        array_pop($output);

        return $output;
    }

    /**
     * Get string representation of the object.
     */
    public function __toString(): string
    {
        return $this->getValue();
    }
}
