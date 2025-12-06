<?php

namespace Tests\Feature\FlashSale;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // تأكد أن Redis خالي قبل كل اختبار
        Redis::flushall();
    }

    public function test_webhook_is_idempotent()
    {
        $product = Product::factory()->create(['stock' => 5]);

        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'status' => 'consumed', // لازم يكون hold تم استهلاكه بالفعل
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $order = Order::factory()->create([
            'hold_id' => $hold->id,
            'status' => 'pending'
        ]);

        $payload = [
            'order_id' => $order->id,
            'status' => 'success',
            'idempotency_key' => 'KEY123'
        ];

        // استدعاء الـ webhook لأول مرة
        $this->postJson('/api/payments/webhook', $payload)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook processed',
            ]);

        // استدعاء الـ webhook مرة ثانية (idempotency)
        $this->postJson('/api/payments/webhook', $payload)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook already processed',
            ]);

        // التأكد أن حالة الـ order تغيرت لـ paid
        $this->assertEquals('paid', $order->fresh()->status);
    }

    public function test_webhook_before_order_creation_is_safe()
    {
        $payload = [
            'order_id' => 99999, // order غير موجود
            'status' => 'success',
            'idempotency_key' => 'LATE999'
        ];

        $this->postJson('/api/payments/webhook', $payload)
            ->assertStatus(202)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook deferred; order not yet created',
            ]);

        // Redis يجب أن يحتوي على المفتاح المؤجل
        $deferredKey = "payment_webhook:deferred:99999:LATE999";
        $this->assertNotNull(Redis::get($deferredKey));
    }
}
