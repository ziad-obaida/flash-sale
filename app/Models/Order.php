<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['hold_id', 'customer_info', 'total_cents', 'status', 'paid_at'];

    protected $casts = [
        'customer_info' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * العلاقة مع Hold
     */
    public function hold(): BelongsTo
    {
        return $this->belongsTo(Hold::class);
    }

    /**
     * تحقق إن الأوردر مدفوع
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
