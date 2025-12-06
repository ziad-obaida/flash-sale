<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => 'Test Product ' . fake()->word(),
            'price_cents' => fake()->numberBetween(1000, 50000),
            'stock' => fake()->numberBetween(1, 50),
        ];
    }
}
