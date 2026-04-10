<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPoint extends Model
{
    protected $guarded = ['id'];

    public function client() { return $this->belongsTo(Client::class); }
    public function order()  { return $this->belongsTo(Order::class); }

    /** Customer এর total points */
    public static function balanceFor(int $clientId, string $senderId): int
    {
        return (int) static::where('client_id', $clientId)
            ->where('sender_id', $senderId)
            ->sum('points');
    }

    /** Order থেকে points earn করা (exact amount from cart item loop) */
    public static function earnFromOrder(Order $order, int $exactPoints): void
    {
        if ($exactPoints <= 0) return;

        static::create([
            'client_id'     => $order->client_id,
            'sender_id'     => $order->sender_id ?? $order->customer_phone,
            'customer_name' => $order->customer_name,
            'customer_phone'=> $order->customer_phone,
            'points'        => $exactPoints,
            'type'          => 'earned',
            'order_id'      => $order->id,
            'note'          => "Order #{$order->id} থেকে অর্জিত",
        ]);
    }
}
