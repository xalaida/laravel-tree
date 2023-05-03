<?php

namespace Nevadskiy\Tree\Tests\Database\Factories;

use Nevadskiy\Tree\Tests\App\CategoryWithCustomSourceColumn;

class CategoryWithCustomSourceColumnFactory extends CategoryFactory
{
    /**
     * {@inheritdoc}
     */
    protected $model = CategoryWithCustomSourceColumn::class;
}
