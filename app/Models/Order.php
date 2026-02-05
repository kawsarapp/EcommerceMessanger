<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
	protected $fillable = [
        'client_id',
		'sender_id',
		'customer_name',
		'customer_image',
		'customer_phone',
		'customer_email',
		'division',
		'district',
		'shipping_address',
		'total_amount',
		'order_status',
		'payment_status',
		'payment_method',
		'transaction_id',
		'customer_note',
		'admin_note'

		
    ];
	
    public function items()
		{
			return $this->hasMany(OrderItem::class);
		}

	public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }


	public function orderItems(): HasMany
{
    return $this->hasMany(OrderItem::class);
}

}
