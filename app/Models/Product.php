<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'price_cents', 'stock'];

    /**
     * العلاقة مع الـ Holds
     */
    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }

    /**
     * حساب الـ available stock الحقيقي (Active holds + paid orders)
     */
    public function availableStock(): int
    {
        $activeHolds = $this->holds()->where('status', 'active')->sum('qty');
        $paidOrders = $this->holds()->whereHas('order', function ($q) {
            $q->where('status', 'paid');
        })->sum('qty');

        return $this->stock - $activeHolds - $paidOrders;
    }
}
