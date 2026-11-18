<?php

namespace Database\Factories;

use App\Enums\UnitType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'sku' => strtoupper(fake()->unique()->bothify('LM-####??')),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 0.5, 25),
            'unit_type' => UnitType::Unit,
            'is_perishable' => false,
            'location' => 'Pasillo '.fake()->numberBetween(1, 8),
            'is_active' => true,
        ];
    }

    public function byWeight(): static
    {
        return $this->state(['unit_type' => UnitType::Weight, 'is_perishable' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
