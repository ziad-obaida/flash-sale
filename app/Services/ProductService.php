<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function fetchProductWithStock(int $id): array
    {
        $product = Product::find($id);

        if (!$product) {
            Redis::incr('metrics:product_fetch_not_found');
            Log::channel('flashsale')->warning('Product not found', ['product_id' => $id]);
            return ['error' => 'Product not found', 'status' => 404];
        }

        $redisKey = "product:{$id}:stock";

        if (Redis::exists($redisKey)) {
            $stock = Redis::get($redisKey);
            Redis::incr('metrics:product_redis_hit');
            Log::channel('flashsale')->info('Product fetched from Redis', ['product_id' => $id, 'stock' => $stock]);
        } else {
            $stock = $product->stock;
            Redis::set($redisKey, $stock);
            Redis::incr('metrics:product_redis_miss');
            Log::channel('flashsale')->info('Product fetched from DB and cached in Redis', ['product_id' => $id, 'stock' => $stock]);
        }

        return [
            'id'          => $product->id,
            'name'        => $product->name,
            'price_cents' => $product->price_cents,
            'stock'       => (int)$stock
        ];
    }
}
