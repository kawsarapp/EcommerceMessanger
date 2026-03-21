<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['refund_amount' => 'decimal:2'];

    public function client() { return $this->belongsTo(Client::class); }
    public function order()  { return $this->belongsTo(Order::class); }

    public function isResolved(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'refunded']);
    }
}
