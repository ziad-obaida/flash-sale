<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Product;
use App\Jobs\ExpireHoldJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HoldService
{
    public function createHold(int $productId, int $qty): array
    {
        $redisKey = "product:{$productId}:stock";

        $product = Product::find($productId);

        if (!Redis::exists($redisKey)) {
            Redis::set($redisKey, $product->stock);
        }

        // Atomic decrement
        $script = <<<LUA
        local stock = tonumber(redis.call('get', KEYS[1]) or 0)
        local qty = tonumber(ARGV[1])
        if stock >= qty then
            return redis.call('decrby', KEYS[1], qty)
        else
            return -1
        end
        LUA;

        $newStock = Redis::eval($script, 1, $redisKey, $qty);

        if ($newStock == -1) {
            Redis::incr('metrics:holds_failed_stock');
            Log::channel('flashsale')->warning('Hold failed - insufficient stock', [
                'product_id' => $productId,
                'requested_qty' => $qty,
                'available_stock' => Redis::get($redisKey),
            ]);
            return ['error' => 'Not enough stock available', 'status' => 400];
        }

        $hold = DB::transaction(function () use ($productId, $qty) {
            return Hold::create([
                'product_id' => $productId,
                'qty'        => $qty,
                'status'     => 'active',
                'expires_at' => Carbon::now()->addSeconds(120),
            ]);
        });

        ExpireHoldJob::dispatch($hold->id)->delay(now()->addSeconds(120));

        Redis::incr('metrics:holds_created');
        Log::channel('flashsale')->info('Hold created', [
            'hold_id' => $hold->id,
            'product_id' => $hold->product_id,
            'qty' => $hold->qty,
            'expires_at' => $hold->expires_at,
            'remaining_stock' => $newStock,
        ]);

        return [
            'hold_id'    => $hold->id,
            'expires_at' => $hold->expires_at,
            'remaining'  => $newStock,
        ];
    }
}
