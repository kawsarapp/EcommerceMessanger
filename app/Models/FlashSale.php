<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FlashSale extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'product_ids'  => 'array',
        'category_ids' => 'array',
        'starts_at'    => 'datetime',
        'ends_at'      => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function client() { return $this->belongsTo(Client::class); }

    /** এখন active flash sale আছে কিনা */
    public function isLive(): bool
    {
        if (!$this->is_active) return false;
        $now = Carbon::now();
        return $now->gte($this->starts_at) && $now->lte($this->ends_at);
    }

    /** Product এর জন্য ছাড়ের দাম calculate */
    public function discountedPrice(float $originalPrice): float
    {
        if ($this->discount_type === 'percent') {
            return round($originalPrice * (1 - $this->discount_percent / 100), 2);
        }
        return max(0, $originalPrice - $this->discount_amount);
    }

    /** Client এর active flash sale (static helper) */
    public static function activeForClient(int $clientId): ?self
    {
        return static::where('client_id', $clientId)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latest('starts_at')
            ->first();
    }
}
