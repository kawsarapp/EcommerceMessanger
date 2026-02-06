<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSession extends Model
{
    // ডাটাবেসে যে কলামগুলো সেভ হবে
    protected $fillable = [
        'sender_id', 
        'client_id', 
        'customer_info', 
        'is_human_agent_active',
        'status'
    ];

    // customer_info কলামটি JSON হিসেবে কাজ করবে
    protected $casts = [
        'customer_info' => 'array',
        'is_human_agent_active' => 'boolean',
    ];
}