<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSession extends Model
{
    protected $fillable = [
        'sender_id', 
        'client_id',
        'platform',         // 🔥 whatsapp / messenger / instagram / telegram
        'customer_info', 
        'is_human_agent_active',
        'status',
        'reminder_status',
        'last_interacted_at'
    ];

    protected $casts = [
        'customer_info' => 'array',
        'is_human_agent_active' => 'boolean',
        'last_interacted_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}