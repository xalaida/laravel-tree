<?php

namespace Nevadskiy\Tree\Tests\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait Uuid
{
    /**
     * Boot the trait.
     */
    public static function bootUuid(): void
    {
        static::creating(static function (self $model) {
            $model->setAttribute($model->getKeyName(), (string) Str::orderedUuid());
        });
    }

    /**
     * @inheritdoc
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * @inheritdoc
     */
    public function getIncrementing(): bool
    {
        return false;
    }
}
