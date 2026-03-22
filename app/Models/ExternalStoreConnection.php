<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExternalStoreConnection extends Model
{
    protected $fillable = [
        'client_id',
        'platform',
        'endpoint_url',
        'api_key',
        'api_secret',
        'webhook_secret',
        'is_active',
        'last_tested_at',
        'last_test_passed',
        'last_test_error',
        'last_synced_at',
        'meta',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'last_test_passed' => 'boolean',
        'last_tested_at'  => 'datetime',
        'last_synced_at'  => 'datetime',
        'meta'            => 'array',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * মাথায় রাখো: API Key publicly readable নয়
     * এখানে masked version return করবে UI এর জন্য
     */
    public function getMaskedApiKeyAttribute(): string
    {
        $key = $this->api_key;
        if (strlen($key) <= 8) return str_repeat('*', strlen($key));
        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }

    /**
     * নতুন API Key generate করো
     */
    public static function generateApiKey(): string
    {
        return 'nc_' . Str::random(32);
    }

    /**
     * Platform label
     */
    public function getPlatformLabelAttribute(): string
    {
        return match($this->platform) {
            'wordpress' => '🔵 WordPress / WooCommerce',
            'shopify'   => '🟢 Shopify',
            'custom'    => '🟠 Custom REST API',
            default     => ucfirst($this->platform),
        };
    }

    /**
     * Connection status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->is_active) return 'inactive';
        if ($this->last_test_passed === true) return 'connected';
        if ($this->last_test_passed === false) return 'error';
        return 'unknown';
    }
}
