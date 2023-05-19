<?php

namespace Nevadskiy\Tree\Tests;

use Nevadskiy\Tree\Tests\Database\Factories\CategoryWithCustomSourceColumnFactory;

class CustomSourceColumnTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_path_from_custom_source_column(): void
    {
        $parent = CategoryWithCustomSourceColumnFactory::new()->create([
            'name' => 'books'
        ]);

        $child = CategoryWithCustomSourceColumnFactory::new()
            ->forParent($parent)
            ->create([
                'name' => 'science'
            ]);

        self::assertEquals('books.science', $child->getPath()->getValue());
        self::assertCount(1, $parent->descendants);
        self::assertTrue($parent->descendants->first()->is($child));
    }
}
