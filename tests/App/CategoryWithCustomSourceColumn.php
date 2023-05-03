<?php

namespace Nevadskiy\Tree\Tests\App;

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
