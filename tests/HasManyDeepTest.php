<?php

namespace Nevadskiy\Tree\Tests;

use Nevadskiy\Tree\Tests\Support\Factories\CategoryFactory;
use Nevadskiy\Tree\Tests\Support\Factories\ProductFactory;

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

    // @todo test eager loading...

    // @todo test whereHas method...
}
