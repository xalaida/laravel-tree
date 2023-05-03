<?php

namespace Nevadskiy\Tree\Tests\Support\Factories;

use Nevadskiy\Tree\Tests\Support\Models\CategoryWithCustomSourceColumn;

class CategoryWithCustomSourceColumnFactory extends CategoryFactory
{
    /**
     * {@inheritdoc}
     */
    protected $model = CategoryWithCustomSourceColumn::class;
}
