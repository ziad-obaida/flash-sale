<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Hold extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'qty', 'status', 'expires_at', 'consumed_by_order_id'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * العلاقة مع المنتج
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * العلاقة مع الأوردر (واحد لواحد)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'consumed_by_order_id');
    }

    /**
     * تحقق إن Hold مازال صالح
     */
    public function isValid(): bool
    {
        return $this->status === 'active' && $this->expires_at && $this->expires_at->isFuture();
    }
}
