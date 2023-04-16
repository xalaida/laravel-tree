<?php

namespace Nevadskiy\Tree\Tests;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Tests\Support\Factories\CategoryFactory;
use Nevadskiy\Tree\Tests\Support\Traits\Uuid;

class UuidCategoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_path_attribute(): void
    {
        $category = UuidCategoryFactory::new()
            ->withAncestors(2)
            ->create();

        self::assertEquals(3, $category->getPath()->getDepth());
        self::assertEquals($category->parent->parent->getPathSource(), $category->getPath()->segments()[0]);
        self::assertEquals($category->parent->getPathSource(), $category->getPath()->segments()[1]);
        self::assertEquals($category->getPathSource(), $category->getPath()->segments()[2]);
    }
}

class UuidCategory extends Model
{
    use Uuid; // "Uuid" has to be included before "AsTree" trait.
    use AsTree; // "AsTree" has to be included after "Uuid" trait.

    /**
     * {@inheritdoc}
     */
    protected $table = 'uuid_categories';
}

class UuidCategoryFactory extends CategoryFactory
{
    /**
     * {@inheritdoc}
     */
    protected $model = UuidCategory::class;
}
