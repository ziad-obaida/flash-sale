<?php

namespace Database\Factories;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class HoldFactory extends Factory
{
    protected $model = Hold::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),  // ينشئ منتج تلقائيًا إذا لم يُحدد
            'qty' => $this->faker->numberBetween(1, 5),
            'status' => 'pending',
        ];
    }
}
