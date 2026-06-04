<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FonnteMessageLog extends Model
{
    protected $fillable = [
        'order_id',
        'event_type',
        'target',
        'status',
        'message',
        'response_data',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}