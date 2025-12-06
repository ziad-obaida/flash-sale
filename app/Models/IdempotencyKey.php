<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'request_payload', 'processed', 'result', 'processed_at'];

    protected $casts = [
        'request_payload' => 'array',
        'result' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
