<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    
	protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
		'price',
    ];
	
	public function product()
		{
			return $this->belongsTo(Product::class);
		}

		public function order()
		{
			return $this->belongsTo(Order::class);
		}
	
	
}
