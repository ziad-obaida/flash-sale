<?php

namespace Tests\Feature\FlashSale;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;
use Carbon\Carbon;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_from_active_hold()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $product = Product::create([
            'name' => 'Laptop',
            'price_cents' => 100000,
            'stock' => 5,
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 2,
            'status' => 'active',
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/orders', [
            'hold_id' => $hold->id,
            'customer_info' => ['name' => 'John Doe', 'email' => 'john@example.com']
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['order_id']
            ]);

        $this->assertDatabaseHas('orders', [
            'hold_id' => $hold->id,
            'total_cents' => $product->price_cents * $hold->qty,
        ]);
    }

    public function test_cannot_create_order_from_expired_hold()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $product = Product::create(['name' => 'Laptop', 'price_cents' => 100000, 'stock' => 5]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 1,
            'status' => 'active',
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $response = $this->postJson('/api/orders', ['hold_id' => $hold->id]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Hold expired',
            ]);
    }

    public function test_cannot_create_order_from_used_hold()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $product = Product::create(['name' => 'Laptop', 'price_cents' => 100000, 'stock' => 5]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 1,
            'status' => 'consumed', // متوافق مع الكونترولر
            'expires_at' => Carbon::now()->addMinutes(10),
            'consumed_by_order_id' => 1,
        ]);

        $response = $this->postJson('/api/orders', ['hold_id' => $hold->id]);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Hold expired',
            ]);
    }

    public function test_cannot_create_order_from_invalid_hold()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/orders', ['hold_id' => 9999]);
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired hold',
            ]);
    }
}
