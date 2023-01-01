<?php

namespace Nevadskiy\Tree\Tests;

use Nevadskiy\Tree\Exceptions\CircularReferenceException;
use Nevadskiy\Tree\Tests\Support\Factories\CategoryFactory;
use Nevadskiy\Tree\Tests\Support\Models\Category;

class CategoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_path_attribute(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(2)
            ->create();

        self::assertEquals(3, $category->getPath()->getDepth());
        self::assertEquals($category->parent->parent->getPathSource(), $category->getPath()->segments()[0]);
        self::assertEquals($category->parent->getPathSource(), $category->getPath()->segments()[1]);
        self::assertEquals($category->getPathSource(), $category->getPath()->segments()[2]);
    }

    /**
     * @test
     */
    public function it_has_relation_with_parent_category(): void
    {
        $parent = CategoryFactory::new()->create();

        $category = CategoryFactory::new()
            ->forParent($parent)
            ->create();

        self::assertTrue($category->parent->is($parent));
    }

    /**
     * @test
     */
    public function it_has_relation_with_children_categories(): void
    {
        $parent = CategoryFactory::new()->create();

        $children = CategoryFactory::new()
            ->forParent($parent)
            ->count(3)
            ->create();

        self::assertCount(3, $parent->children);
        self::assertTrue($parent->children[0]->is($children[0]));
        self::assertTrue($parent->children[1]->is($children[1]));
        self::assertTrue($parent->children[2]->is($children[2]));
    }

    /**
     * @test
     */
    public function it_has_relation_with_ancestor_categories(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(3)
            ->create();

        $this->assertDatabaseCount(Category::class, 4);

        self::assertCount(3, $category->ancestors);
        self::assertNotContains($category, $category->ancestors);
    }

    /**
     * @test
     */
    public function it_has_relation_with_descendant_categories(): void
    {
        $root = CategoryFactory::new()->create();

        [$child] = CategoryFactory::new()
            ->forParent($root)
            ->count(2)
            ->create();

        [$descendant] = CategoryFactory::new()
            ->forParent($child)
            ->count(2)
            ->create();

        self::assertCount(4, $root->descendants);
        self::assertTrue($root->descendants->contains($child));
        self::assertTrue($root->descendants->contains($descendant));
    }

    /**
     * @test
     */
    public function it_can_be_ordered_by_depth_asc(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(3)
            ->create();

        $categories = Category::query()->orderByDepth()->get();

        self::assertTrue($categories->last()->is($category));
        self::assertEquals(1, $categories->first()->getPath()->getDepth());
        self::assertEquals(4, $categories->last()->getPath()->getDepth());
    }

    /**
     * @test
     */
    public function it_can_be_ordered_by_depth_desc(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(3)
            ->create();

        $categories = Category::query()->orderByDepthDesc()->get();

        self::assertTrue($categories->first()->is($category));
        self::assertEquals(1, $categories->last()->getPath()->getDepth());
        self::assertEquals(4, $categories->first()->getPath()->getDepth());
    }

    /**
     * @test
     */
    public function it_can_be_filtered_by_depth(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(2)
            ->create();

        $categories = Category::query()->whereDepth(2)->get();

        self::assertCount(1, $categories);
        self::assertTrue($categories->first()->is($category->parent));
        self::assertEquals(2, $categories->first()->getPath()->getDepth());
    }

    /**
     * @test
     */
    public function it_joins_ancestors_to_node_collection(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(2)
            ->create();

        $categories = $category->joinAncestors();

        self::assertCount(3, $categories);
        self::assertTrue($categories[0]->is($category));
        self::assertTrue($categories[1]->is($category->parent));
        self::assertTrue($categories[2]->is($category->parent->parent));
    }

    /**
     * @test
     */
    public function it_eager_loads_category_with_ancestors(): void
    {
        CategoryFactory::new()
            ->withAncestors(2)
            ->create();

        Category::query()->getConnection()->enableQueryLog();

        $categories = Category::query()
            ->with('ancestors')
            ->orderByDepth()
            ->get();

        self::assertCount(3, $categories);
        self::assertCount(0, $categories[0]->ancestors);
        self::assertCount(1, $categories[1]->ancestors);
        self::assertCount(2, $categories[2]->ancestors);
        self::assertCount(2, Category::query()->getConnection()->getQueryLog());
    }

    /**
     * @test
     */
    public function it_eager_loads_category_with_descendants(): void
    {
        CategoryFactory::new()
            ->withAncestors(2)
            ->create();

        Category::query()->getConnection()->enableQueryLog();

        $categories = Category::query()
            ->with('descendants')
            ->orderByDepth()
            ->get();

        self::assertCount(3, $categories);
        self::assertCount(2, $categories[0]->descendants);
        self::assertCount(1, $categories[1]->descendants);
        self::assertCount(0, $categories[2]->descendants);
        self::assertCount(2, Category::query()->getConnection()->getQueryLog());
    }

    /**
     * @test
     */
    public function it_updates_path_of_subtree_when_parent_category_is_changed(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors()
            ->create();

        $anotherCategory = CategoryFactory::new()
            ->withAncestors()
            ->create();

        $category->parent->parent()->associate($anotherCategory);
        $category->parent->save();

        $category->refresh();

        self::assertEquals(4, $category->getPath()->getDepth());
        self::assertEquals($anotherCategory->parent->getPathSource(), $category->getPath()->segments()[0]);
    }

    /**
     * @test
     */
    public function it_updates_path_of_subtree_when_category_moves_to_root(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(2)
            ->create();

        $category->parent->parent()->disassociate();
        $category->parent->save();

        $category->refresh();

        self::assertEquals($category->parent->getPathSource(), $category->getPath()->segments()[0]);
        self::assertEquals(1, $category->parent->getPath()->getDepth());
        self::assertEquals(2, $category->getPath()->getDepth());
    }

    /**
     * @test
     */
    public function it_detects_circular_dependency(): void
    {
        $category = CategoryFactory::new()->create();

        $anotherCategory = CategoryFactory::new()
            ->forParent($category)
            ->create();

        $this->expectException(CircularReferenceException::class);

        $category->parent()->associate($anotherCategory);
        $category->save();
    }

    /**
     * @test
     */
    public function it_detects_circular_dependency_when_category_is_moved_inside_itself(): void
    {
        $category = CategoryFactory::new()
            ->withAncestors(2)
            ->create();

        $this->expectException(CircularReferenceException::class);

        $category->parent->parent->parent()->associate($category);
        $category->parent->parent->save();
    }

    /**
     * @test
     */
    public function it_can_determine_whether_it_was_moved(): void
    {
        $category = CategoryFactory::new()->create();

        self::assertFalse($category->wasMoved());

        $anotherCategory = CategoryFactory::new()
            ->forParent($category)
            ->create();

        self::assertFalse($anotherCategory->fresh()->wasMoved());

        $anotherCategory->parent()->disassociate();
        $anotherCategory->save();

        self::assertTrue($anotherCategory->wasMoved());
    }

    /**
     * @test
     */
    public function it_can_determine_whether_it_is_moving(): void
    {
        $category = CategoryFactory::new()->create();

        self::assertFalse($category->isMoving());

        $anotherCategory = CategoryFactory::new()
            ->forParent($category)
            ->create();

        self::assertFalse($anotherCategory->isMoving());

        $anotherCategory->parent()->disassociate();

        self::assertTrue($anotherCategory->isMoving());

        $anotherCategory->save();

        self::assertFalse($anotherCategory->isMoving());
    }
}
