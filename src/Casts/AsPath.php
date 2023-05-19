<?php

namespace Nevadskiy\Tree\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Str;
use Nevadskiy\Tree\ValueObjects\Path;
use RuntimeException;

class AsPath implements CastsAttributes
{
    /**
     * @inheritdoc
     */
    public function get($model, string $key, $value, array $attributes): ?Path
    {
        if (! isset($attributes[$key])) {
            return null;
        }

        if ($this->usesPgsqlConnection($model)) {
            $value = $this->transformPgsqlPathFromDatabase($value);
        }

        return new Path($value);
    }

    /**
     * @inheritdoc
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (! $value instanceof Path) {
            throw new RuntimeException(sprintf('The "%s" is not a Path instance.', $key));
        }

        $path = $value->getValue();

        if ($this->usesPgsqlConnection($model)) {
            $path = $this->transformPgsqlPathToDatabase($path);
        }

        return $path;
    }

    /**
     * Determine if the model uses the PostgreSQL connection.
     */
    protected function usesPgsqlConnection(Model $model): bool
    {
        return $model->getConnection() instanceof PostgresConnection;
    }

    /**
     * Transform the PostgreSQL path to database.
     */
    protected function transformPgsqlPathToDatabase(string $path): string
    {
        if (Str::containsAll($path, ['-', '_'])) {
            throw new RuntimeException('The path cannot have mixed "-" and "_" characters.');
        }

        return Str::replace('-', '_', $path);
    }

    /**
     * Transform the PostgreSQL path value from database.
     */
    protected function transformPgsqlPathFromDatabase(string $path): string
    {
        return Str::replace('_', '-', $path);
    }
}
