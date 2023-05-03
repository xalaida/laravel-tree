<?php

namespace Nevadskiy\Tree\Tests\App;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Relations\HasManyDeep;

class Category extends Model
{
    use AsTree;

    public function products(): HasManyDeep
    {
        return HasManyDeep::between($this, Product::class);
    }
}
