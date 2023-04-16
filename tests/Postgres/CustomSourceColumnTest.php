<?php

namespace Nevadskiy\Tree\Tests\Postgres;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\AsTree;
use Nevadskiy\Tree\Tests\Support\Factories\CategoryFactory;

class CustomSourceColumnPostgresTest extends PostgresTestCase
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

class CategoryWithCustomSourceColumn extends Model
{
    use AsTree;

    protected $table = 'categories';

    public function getPathSourceColumn(): string
    {
        return 'name';
    }
}

class CategoryWithCustomSourceColumnFactory extends CategoryFactory
{
    protected $model = CategoryWithCustomSourceColumn::class;
}
