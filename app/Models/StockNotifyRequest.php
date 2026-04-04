<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockNotifyRequest extends Model
{
    protected $guarded = ['id'];

    public function client()  { return $this->belongsTo(Client::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
