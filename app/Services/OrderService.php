<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function createOrder(Hold $hold, array $customerInfo = null)
    {
        if (!$hold) {
            Redis::incr('metrics:orders_failed_invalid_hold');
            return ['error' => 'Invalid hold', 'status' => 400];
        }

        // Transaction to ensure atomicity
        try {
            $order = DB::transaction(function () use ($hold, $customerInfo) {
                // Lock the hold to prevent race conditions
                $hold = Hold::where('id', $hold->id)
                    ->lockForUpdate()
                    ->first();

                // Validate hold status and expiry
                if ($hold->status === 'expired') {
                    throw new \Exception('Hold expired');
                }

                if ($hold->status === 'cancelled') {
                    throw new \Exception('Hold cancelled');
                }

                if ($hold->status === 'completed') {
                    throw new \Exception('Hold already completed');
                }

                if (!in_array($hold->status, ['active', 'consumed'])) {
                    throw new \Exception('Invalid hold status');
                }

                // Check time-based expiry
                if ($hold->expires_at->lt(now())) {
                    $hold->update(['status' => 'expired']);

                    // Release stock back to Redis
                    $redisKey = "product:{$hold->product_id}:stock";
                    Redis::incrby($redisKey, $hold->qty);

                    throw new \Exception('Hold expired');
                }

                // Check if already consumed
                if ($hold->consumed_by_order_id) {
                    throw new \Exception('Hold already consumed');
                }

                // Create the order
                $order = Order::create([
                    'hold_id'       => $hold->id,
                    'customer_info' => $customerInfo,
                    'total_cents'   => $hold->qty * $hold->product->price_cents,
                    'status'        => 'pending',
                ]);

                // Mark hold as consumed
                $hold->update([
                    'status' => 'consumed',
                    'consumed_by_order_id' => $order->id,
                ]);

                Log::channel('flashsale')->info('Order created', [
                    'order_id' => $order->id,
                    'hold_id'  => $hold->id,
                ]);

                // Check for deferred webhooks (webhook arrived before order creation)
                $this->processDeferredWebhooks($order->id);

                return $order;
            });

            Redis::incr('metrics:orders_created');
            return $order;
        } catch (\Exception $e) {
            Redis::incr('metrics:orders_failed_invalid_hold');
            Log::channel('flashsale')->warning('Order creation failed', [
                'hold_id' => $hold->id,
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage(), 'status' => 400];
        }
    }

    /**
     * Process any webhooks that arrived before order creation
     */
    private function processDeferredWebhooks(int $orderId): void
    {
        $pattern = "payment_webhook:deferred:{$orderId}:*";
        $keys = Redis::keys($pattern);

        foreach ($keys as $key) {
            $payload = json_decode(Redis::get($key), true);
            if ($payload) {
                Log::channel('flashsale')->info('Processing deferred webhook', [
                    'order_id' => $orderId,
                    'key' => $key
                ]);

                // Process the webhook
                $webhookService = app(\App\Services\PaymentWebhookService::class);
                $webhookService->handleWebhook($payload);

                // Delete the deferred webhook
                Redis::del($key);
            }
        }
    }
}
