<?php

namespace App\Jobs;

use App\Models\Hold;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ExpireHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $holdId;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 3;

    public function __construct(int $holdId)
    {
        $this->holdId = $holdId;
    }

    public function handle(): void
    {
        DB::transaction(function () {
            // Lock the hold to prevent race conditions with webhook
            $hold = Hold::where('id', $this->holdId)
                ->lockForUpdate()
                ->first();

            if (!$hold) {
                Log::channel('flashsale')->warning('Hold not found for expiry', [
                    'hold_id' => $this->holdId
                ]);
                return;
            }

            // Only expire if still active or consumed (not yet paid)
            // If status is 'completed', payment succeeded, don't expire
            // If status is 'cancelled', already handled
            // If status is 'expired', already expired (duplicate job)
            if (!in_array($hold->status, ['active', 'consumed'])) {
                Log::channel('flashsale')->info('Hold already processed, skipping expiry', [
                    'hold_id' => $hold->id,
                    'status' => $hold->status
                ]);
                return;
            }

            // Double-check if actually expired (safety check)
            if ($hold->expires_at->isFuture()) {
                Log::channel('flashsale')->info('Hold not yet expired, skipping', [
                    'hold_id' => $hold->id,
                    'expires_at' => $hold->expires_at->toDateTimeString()
                ]);
                return;
            }

            // Mark as expired
            $hold->update(['status' => 'expired']);

            // Return THIS HOLD's stock to Redis (atomic increment)
            $redisKey = "product:{$hold->product_id}:stock";
            $newStock = Redis::incrby($redisKey, $hold->qty);

            Redis::incr('metrics:holds_expired');

            Log::channel('flashsale')->info('Hold expired and stock released', [
                'hold_id' => $hold->id,
                'product_id' => $hold->product_id,
                'qty_released' => $hold->qty,
                'new_redis_stock' => $newStock
            ]);
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('flashsale')->error('ExpireHoldJob failed', [
            'hold_id' => $this->holdId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optionally: send alert or notification
    }
}
