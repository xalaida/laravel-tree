<?php

namespace Nevadskiy\Tree\Tests;

use Nevadskiy\Tree\Tests\Support\Factories\CategoryFactory;
use Nevadskiy\Tree\Tests\Support\Models\Category;

class TreeTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_nodes_to_tree(): void
    {
        $grandParent1 = CategoryFactory::new()->create();

        $grandParent2 = CategoryFactory::new()->create();

        $parent1 = CategoryFactory::new()
            ->forParent($grandParent1)
            ->create();

        $parent2 = CategoryFactory::new()
            ->forParent($grandParent1)
            ->create();

        $child1 = CategoryFactory::new()
            ->forParent($parent1)
            ->create();

        $child2 = CategoryFactory::new()
            ->forParent($parent1)
            ->create();

        $categories = Category::query()
            ->get()
            ->tree();

        self::assertCount(2, $categories);
        self::assertTrue($categories[0]->is($grandParent1));
        self::assertTrue($categories[1]->is($grandParent2));

        self::assertCount(2, $categories[0]->children);
        self::assertEmpty($categories[1]->children);
        self::assertTrue($categories[0]->children[0]->is($parent1));
        self::assertTrue($categories[0]->children[1]->is($parent2));

        self::assertCount(2, $categories[0]->children[0]->children);
        self::assertEmpty($categories[0]->children[1]->children);
        self::assertTrue($categories[0]->children[0]->children[0]->is($child1));
        self::assertTrue($categories[0]->children[0]->children[1]->is($child2));
    }

    /**
     * @test
     */
    public function it_converts_to_tree_when_no_children(): void
    {
        $category1 = CategoryFactory::new()->create();

        $category2 = CategoryFactory::new()->create();

        $categories = Category::query()
            ->get()
            ->tree();

        self::assertCount(2, $categories);
        self::assertTrue($categories[0]->is($category1));
        self::assertTrue($categories[1]->is($category2));
        self::assertEmpty($categories[0]->children);
        self::assertEmpty($categories[1]->children);
    }

    /**
     * @test
     */
    public function it_converts_to_tree_when_no_nodes_to_link(): void
    {
        $grandParent = CategoryFactory::new()->create();

        $parent = CategoryFactory::new()
            ->forParent($grandParent)
            ->create();

        $child = CategoryFactory::new()
            ->forParent($parent)
            ->create();

        $categories = Category::query()
            ->whereKeyNot($parent->getKey())
            ->get()
            ->tree();

        self::assertCount(1, $categories);
        self::assertEmpty($categories[0]->children);
    }

    /**
     * @test
     */
    public function it_returns_empty_collection_when_no_nodes(): void
    {
        $categories = Category::query()
            ->get()
            ->tree();

        self::assertEmpty($categories);
    }

    /**
     * @test
     */
    public function it_converts_to_tree_when_no_root_nodes(): void
    {
        $grandParent = CategoryFactory::new()->create();

        $parent = CategoryFactory::new()
            ->forParent($grandParent)
            ->create();

        $child = CategoryFactory::new()
            ->forParent($parent)
            ->create();

        $categories = Category::query()
            ->whereKeyNot($grandParent->getKey())
            ->get()
            ->tree();

        self::assertCount(1, $categories);
        self::assertTrue($categories[0]->is($parent));
        self::assertCount(1, $categories[0]->children);
        self::assertTrue($categories[0]->children[0]->is($child));
    }
}
