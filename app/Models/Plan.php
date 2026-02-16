<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    

protected $fillable = [
    'name', 
    'price', 
    'product_limit', 
    'order_limit', 
    'ai_message_limit',
    'description', 
    'color', 
    'is_featured', 
    'is_active'
];

public function clients()
{
    return $this->hasMany(Client::class);
}
    

}
