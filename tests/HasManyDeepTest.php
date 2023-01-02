<?php

namespace Nevadskiy\Tree\Tests;

use Nevadskiy\Tree\Tests\Support\Factories\CategoryFactory;
use Nevadskiy\Tree\Tests\Support\Factories\ProductFactory;
use Nevadskiy\Tree\Tests\Support\Models\Category;

class HasManyDeepTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_products_using_deep_strategy(): void
    {
        $parent = CategoryFactory::new()->create();

        $child = CategoryFactory::new()
            ->forParent($parent)
            ->create();

        $parentProduct = ProductFactory::new()
            ->forCategory($parent)
            ->create();

        $childProduct = ProductFactory::new()
            ->forCategory($child)
            ->create();

        $anotherProduct = ProductFactory::new()->create();

        self::assertCount(2, $parent->products);
        self::assertTrue($parent->products->contains($parentProduct));
        self::assertTrue($parent->products->contains($childProduct));
        self::assertFalse($parent->products->contains($anotherProduct));
    }

    /**
     * @test
     */
    public function it_can_eager_load_products_using_deep_strategy(): void
    {
        $parent = CategoryFactory::new()->create();

        $child = CategoryFactory::new()
            ->forParent($parent)
            ->create();

        $parentProduct = ProductFactory::new()
            ->forCategory($parent)
            ->create();

        $childProduct = ProductFactory::new()
            ->forCategory($child)
            ->create();

        Category::query()->getConnection()->enableQueryLog();

        $categories = Category::query()
            ->with('products')
            ->get();

        self::assertCount(2, $categories[0]->products);
        self::assertTrue($categories[0]->products->contains($parentProduct));
        self::assertTrue($categories[0]->products->contains($childProduct));

        self::assertCount(1, $categories[1]->products);
        self::assertTrue($categories[1]->products->contains($childProduct));
        self::assertCount(2, Category::query()->getConnection()->getQueryLog());
    }

    // @todo test whereHas method...
}
