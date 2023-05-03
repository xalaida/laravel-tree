<?php

namespace Nevadskiy\Tree\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\AsTree;

class CategoryWithCustomSourceColumn extends Model
{
    use AsTree;

    protected $table = 'categories';

    public function getPathSourceColumn(): string
    {
        return 'name';
    }
}
