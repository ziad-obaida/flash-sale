<?php

namespace Tests\Feature\FlashSale;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use App\Models\User;


class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_product_with_correct_stock()
    {
        $user = User::factory()->create(); 
        $this->actingAs($user, 'sanctum');

        $product = Product::factory()->create(['stock' => 5]);
        Redis::set("product:{$product->id}:stock", 5);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'stock' => 5,
                ]
            ]);
    }
}
