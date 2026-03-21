<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['events' => 'array', 'is_active' => 'boolean'];

    public function client() { return $this->belongsTo(Client::class); }

    public const AVAILABLE_EVENTS = [
        'order.created'    => 'নতুন Order',
        'order.updated'    => 'Order Status পরিবর্তন',
        'cart.abandoned'   => 'Cart Abandoned',
        'message.received' => 'নতুন Message',
        'return.requested' => 'Return Request',
    ];
}
