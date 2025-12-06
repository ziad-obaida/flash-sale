<?php

namespace Tests;

use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;

trait CreatesTestData
{
    public function createProduct($stock = 10)
    {
        return Product::factory()->create([
            'stock' => $stock
        ]);
    }

    public function createHold($product, $qty = 1)
    {
        return Hold::factory()->create([
            'product_id' => $product->id,
            'qty' => $qty
        ]);
    }

    public function createOrder($hold)
    {
        return Order::factory()->create([
            'hold_id' => $hold->id,
            'total_cents' => $hold->product->price_cents * $hold->qty
        ]);
    }
}
