<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Referral extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['reward_amount' => 'decimal:2', 'discount_amount' => 'decimal:2'];

    public function client() { return $this->belongsTo(Client::class); }
    public function order()  { return $this->belongsTo(Order::class); }

    /** Unique referral code generate */
    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());
        return $code;
    }

    /** Customer এর referral code get/create */
    public static function getOrCreateForCustomer(int $clientId, string $senderId): self
    {
        return static::firstOrCreate(
            ['client_id' => $clientId, 'sender_id' => $senderId],
            ['referral_code' => static::generateCode(), 'status' => 'pending']
        );
    }
}
