<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Hold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentWebhookService
{
    public function handleWebhook(array $payload)
    {
        $orderId = $payload['order_id'];
        $status = $payload['status'];
        $idempotencyKey = $payload['idempotency_key'];
        $idempRedisKey = "payment_webhook:idemp:{$idempotencyKey}";

        Log::channel('flashsale')->info('Webhook received', $payload);

        // Check idempotency - prevent duplicate processing
        if (!Redis::setnx($idempRedisKey, 1)) {
            Redis::incr('metrics:webhooks_skipped_duplicate');
            Log::channel('flashsale')->info('Webhook skipped - duplicate', [
                'idempotency_key' => $idempotencyKey,
                'order_id' => $orderId,
            ]);
            return [
                'message' => 'Webhook already processed',
                'status' => 200,
                'data' => [],
            ];
        }

        Redis::expire($idempRedisKey, 6 * 3600);

        // Check if order exists
        $order = Order::where('id', $orderId)->first();

        if (!$order) {
            // Defer webhook for later processing
            $defKey = "payment_webhook:deferred:{$orderId}:{$idempotencyKey}";
            Redis::setex($defKey, 86400, json_encode($payload));
            Redis::incr('metrics:webhooks_deferred_before_order');
            Log::channel('flashsale')->info('Webhook deferred - order not found', [
                'order_id' => $orderId,
                'idempotency_key' => $idempotencyKey,
            ]);
            return [
                'message' => 'Webhook deferred; order not yet created',
                'status' => 202,
                'data' => [],
            ];
        }

        try {
            DB::transaction(function () use ($order, $status) {
                // Lock hold and order
                $hold = $order->hold()->lockForUpdate()->first();

                if (!$hold) {
                    throw new Exception('Hold not found for this order');
                }

                // Lock product for stock update
                $product = $hold->product()->lockForUpdate()->first();

                if ($status === 'success') {
                    // Check if hold is still valid (not expired, not already completed)
                    // IMPORTANT: We check status, not time, because ExpireHoldJob might have already marked it expired
                    if ($hold->status === 'expired') {
                        throw new Exception('Hold already expired; payment rejected');
                    }

                    if ($hold->status === 'completed') {
                        // Already processed, this is a duplicate webhook that passed idempotency check race
                        Log::channel('flashsale')->info('Hold already completed, skipping', ['hold_id' => $hold->id]);
                        return;
                    }

                    // Update order to paid
                    $order->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    // Mark hold as completed
                    $hold->update(['status' => 'completed']);

                    // Decrement actual DB stock (this is the final sale)
                    $product->decrement('stock', $hold->qty);

                    // Redis stock is already decremented when hold was created, so no change needed here

                    Redis::incr('metrics:webhooks_success_paid');
                    Log::channel('flashsale')->info('Payment successful', [
                        'order_id' => $order->id,
                        'hold_id' => $hold->id,
                        'new_db_stock' => $product->stock
                    ]);
                } elseif ($status === 'failed') {
                    // Payment failed, release the hold
                    $order->update(['status' => 'cancelled']);

                    // Only release if hold was still active/consumed
                    if (in_array($hold->status, ['active', 'consumed'])) {
                        $hold->update(['status' => 'cancelled']);

                        // Return stock to Redis
                        $redisKey = "product:{$product->id}:stock";
                        Redis::incrby($redisKey, $hold->qty);

                        Redis::incr('metrics:webhooks_failed_released');
                        Log::channel('flashsale')->info('Payment failed, stock released', [
                            'order_id' => $order->id,
                            'hold_id' => $hold->id,
                            'qty_released' => $hold->qty,
                            'new_redis_stock' => Redis::get($redisKey)
                        ]);
                    }
                }
            });
        } catch (Exception $e) {
            Log::channel('flashsale')->error('Webhook processing error', [
                'message' => $e->getMessage(),
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'message' => $e->getMessage(),
                'status' => 400,
                'data' => [],
            ];
        }

        return [
            'message' => 'Webhook processed successfully',
            'status' => 200,
            'data' => [
                'order_id' => $order->id,
                'order_status' => $order->fresh()->status,
            ],
        ];
    }
}
