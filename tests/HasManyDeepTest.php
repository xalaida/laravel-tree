<?php

namespace Nevadskiy\Tree\Tests;

use Illuminate\Database\Eloquent\Builder;
use Nevadskiy\Tree\Tests\App\Category;
use Nevadskiy\Tree\Tests\Database\Factories\CategoryFactory;
use Nevadskiy\Tree\Tests\Database\Factories\ProductFactory;

class HasManyDeepTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_products_using_deep_strategy(): void
    {
        $parentCategory = CategoryFactory::new()->create();

        $childCategory = CategoryFactory::new()
            ->forParent($parentCategory)
            ->create();

        $parentProduct = ProductFactory::new()
            ->forCategory($parentCategory)
            ->create();

        $childProduct = ProductFactory::new()
            ->forCategory($childCategory)
            ->create();

        $anotherProduct = ProductFactory::new()->create();

        self::assertCount(2, $parentCategory->products);
        self::assertTrue($parentCategory->products->contains($parentProduct));
        self::assertTrue($parentCategory->products->contains($childProduct));
        self::assertFalse($parentCategory->products->contains($anotherProduct));
    }

    /**
     * @test
     */
    public function it_does_not_override_props(): void
    {
        $category = CategoryFactory::new()->create(['name' => 'Video Games']);

        ProductFactory::new()
            ->forCategory($category)
            ->create(['name' => 'Sony PlayStation 5']);

        self::assertEquals('Sony PlayStation 5', $category->products->first()->name);
    }

    /**
     * @test
     */
    public function it_can_eager_load_products_using_deep_strategy(): void
    {
        $parentCategory = CategoryFactory::new()->create();

        $childCategory = CategoryFactory::new()
            ->forParent($parentCategory)
            ->create();

        $parentProduct = ProductFactory::new()
            ->forCategory($parentCategory)
            ->create();

        $childProduct = ProductFactory::new()
            ->forCategory($childCategory)
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

    /**
     * @test
     */
    public function it_does_not_override_props_with_eager_loading(): void
    {
        $category = CategoryFactory::new()->create(['name' => 'Video Games']);

        ProductFactory::new()
            ->forCategory($category)
            ->create(['name' => 'Sony PlayStation 5']);

        $categories = Category::query()
            ->with('products')
            ->get();

        self::assertEquals('Sony PlayStation 5', $categories->first()->products->first()->name);
    }

    /**
     * @test
     */
    public function it_filters_nodes_using_where_has_method(): void
    {
        $parentCategory = CategoryFactory::new()->create(['name' => 'Watches']);

        $childCategory = CategoryFactory::new()
            ->forParent($parentCategory)
            ->create(['name' => 'Gold watches']);

        ProductFactory::new()
            ->forCategory($parentCategory)
            ->create(['name' => 'Watch']);

        ProductFactory::new()
            ->forCategory($childCategory)
            ->create(['name' => 'Gold watch']);

        ProductFactory::new()
            ->forCategory($childCategory)
            ->create(['name' => 'Day-Date 36']);

        $categories = Category::query()
            ->whereHas('products', function (Builder $query) {
                $query->where('products.name', 'like', '%watch');
            })
            ->get();

        self::assertCount(2, $categories);
        self::assertTrue($categories->contains($parentCategory));
        self::assertTrue($categories->contains($childCategory));
    }

    /**
     * @test
     */
    public function it_does_not_fail_when_interacting_with_missing_model(): void
    {
        $category = new Category();

        Category::query()->getConnection()->enableQueryLog();

        self::assertEmpty($category->products);
        self::assertEmpty(Category::query()->getConnection()->getQueryLog());
    }
}
