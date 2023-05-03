<?php

namespace Nevadskiy\Tree\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Nevadskiy\Tree\Tests\App\Category;
use Nevadskiy\Tree\Tests\App\Product;
use Nevadskiy\Tree\Tests\Database\Factories\CategoryFactory;
use Nevadskiy\Tree\Tests\Database\Factories\ProductFactory;

class ProductTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_filter_products_by_ancestor_category(): void
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

        $products = Product::query()
            ->whereHas('category', function (Builder $query) use ($parentCategory) {
                $query->whereSelfOrDescendantOf($parentCategory);
            })
            ->get();

        self::assertCount(2, $products);
        self::assertTrue($products->contains($parentProduct));
        self::assertTrue($products->contains($childProduct));
        self::assertFalse($products->contains($anotherProduct));
    }

    /**
     * @test
     */
    public function it_can_filter_products_by_ancestor_category_using_path_join(): void
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

        $products = Product::query()
            ->join('categories', function (JoinClause $join) {
                $join->on(
                    Product::query()->qualifyColumn('category_id'),
                    Category::query()->qualifyColumn('id')
                );
            })
            ->whereSelfOrDescendantOf($parentCategory)
            ->get([
                Product::query()->qualifyColumn('*')
            ]);

        self::assertCount(2, $products);
        self::assertTrue($products->contains($parentProduct));
        self::assertTrue($products->contains($childProduct));
        self::assertFalse($products->contains($anotherProduct));
    }

    /**
     * @test
     */
    public function it_can_filter_products_by_ancestor_category_using_path_join_with_alias(): void
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

        $products = Product::query()
            ->join('categories AS categories_alias', function (JoinClause $join) {
                $join->on(
                    Product::query()->qualifyColumn('category_id'),
                    'categories_alias.id'
                );
            })
            ->whereSelfOrDescendantOf($parentCategory, 'categories_alias.path')
            ->get([
                Product::query()->qualifyColumn('*')
            ]);

        self::assertCount(2, $products);
        self::assertTrue($products->contains($parentProduct));
        self::assertTrue($products->contains($childProduct));
        self::assertFalse($products->contains($anotherProduct));
    }
}
