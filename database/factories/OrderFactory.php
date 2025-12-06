<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\Hold;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'hold_id' => Hold::factory(),
            'status' => 'pending',
            'total_cents' => $this->faker->numberBetween(1000, 50000),
        ];
    }
}
