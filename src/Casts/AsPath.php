<?php

namespace Nevadskiy\Tree\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\PostgresConnection;
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

        // @todo apply only for uuid source column.
        if ($this->usesPgsqlConnection($model)) {
            $value = str_replace('_', '-', $value);
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

        // @todo apply only for uuid source column.
        if ($this->usesPgsqlConnection($model)) {
            // @todo does not work with mixed separators "_" + "-".
            // @todo ensure only [A-Za-z0-9_] regex characters are allowed.
            return str_replace('-', '_', $value->getValue());
        }

        return $value->getValue();
    }

    /**
     * Determine if the model uses the PostgreSQL connection.
     */
    protected function usesPgsqlConnection(Model $model): bool
    {
        return $model->getConnection() instanceof PostgresConnection;
    }
}
