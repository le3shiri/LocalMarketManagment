<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Fruta y verdura', 'Carnicería', 'Pescadería', 'Lácteos y huevos', 'Panadería',
                'Despensa', 'Bebidas', 'Congelados', 'Limpieza', 'Charcutería y quesos',
            ]).' '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }
}
