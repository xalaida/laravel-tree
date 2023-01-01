<?php

namespace Nevadskiy\Tree\Tests\Support\Factories;

use Nevadskiy\Tree\Tests\Support\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * {@inheritdoc}
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
        ];
    }

    /**
     * Make a category instance with the given parent category.
     *
     * @var Category|CategoryFactory $category
     */
    public function forParent($category): self
    {
        return $this->for($category, 'parent');
    }

    /**
     * Make a category instance with a parent category.
     */
    public function withParent(): self
    {
        return $this->forParent(
            static::new()
        );
    }

    /**
     * Make a category instance with ancestors.
     */
    public function withAncestors(int $ancestors = 1): self
    {
        if ($ancestors < 1) {
            return $this;
        }

        return $this->forParent(
            static::new()->withAncestors($ancestors - 1)
        );
    }
}
