<?php

namespace Nevadskiy\Tree\Tests\Support\Factories;

use Illuminate\Database\Eloquent\Model;
use Nevadskiy\Tree\Tests\Support\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Nevadskiy\Tree\Tests\Support\Models\Product;

class ProductFactory extends Factory
{
    /**
     * {@inheritdoc}
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(),
            'category_id' => CategoryFactory::new(),
        ];
    }

    /**
     * Make a product instance with the given category.
     *
     * @var Category|CategoryFactory $category
     */
    public function forCategory($category): self
    {
        if ($category instanceof Model) {
            return $this->state([
                'category_id' => $category->getKey(),
            ]);
        }

        return $this->for($category, 'category');
    }
}
