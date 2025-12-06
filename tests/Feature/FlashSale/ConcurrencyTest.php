<?php

namespace Tests\Feature\FlashSale;

use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_redis_atomic_decrement_cannot_oversell()
    {
        Redis::flushall();
        Redis::set('product:1:stock', 5);

        // محاكاة 10 محاولات حجز stock
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $newStock = Redis::decrby('product:1:stock', 1);
            if ($newStock >= 0) {
                $results[] = true;
            } else {
                // رجعنا القيمة تاني
                Redis::incrby('product:1:stock', 1);
                $results[] = false;
            }
        }

        $successCount = count(array_filter($results));
        $this->assertEquals(5, $successCount, 'Only 5 decrements should succeed');
        $this->assertEquals(0, Redis::get('product:1:stock'));
    }
}
