<?php

namespace Tests\Feature\FlashSale;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;
use App\Models\Hold;
use App\Models\User;
use App\Jobs\ExpireHoldJob;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class HoldTest extends TestCase
{
    use DatabaseMigrations;

    public function test_hold_expiry_releases_stock()
    {
        Redis::flushall();

        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']); // تسجيل الدخول

        $product = Product::create([
            'name' => 'Keyboard',
            'price_cents' => 2000,
            'stock' => 3,
        ]);

        Redis::set("product:{$product->id}:stock", $product->stock);

        $response = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 2
        ]);

        $response->assertStatus(201);

        $holdId = $response->json('data.hold_id'); // تعديل هنا
        $this->assertNotNull($holdId, "Hold ID should not be null");

        // تنفيذ Job يدوياً لمحاكاة انتهاء hold
        ExpireHoldJob::dispatchSync($holdId);

        $this->assertEquals(3, (int) Redis::get("product:{$product->id}:stock"));
    }
}
