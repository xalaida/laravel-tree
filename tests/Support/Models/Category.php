<?php

namespace Nevadskiy\Tree\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Tests\Support\Traits\Uuid;

class Category extends Model
{
    use Uuid; // "Uuid" has to be included before "AsTree" trait.
    use AsTree; // "AsTree" has to be included after "Uuid" trait.
}
