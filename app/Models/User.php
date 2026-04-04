<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'nid_number',
        'password',
        'role',
        'client_id',
        'staff_permissions',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'staff_permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Filament প্যানেল এক্সেস চেক (Security enforcement)
     *
     * Rules:
     *  - Super Admin: always OK
     *  - Staff: blocked completely if plan expired / inactive
     *  - Seller: can login (to see renewal notice) even if plan expired
     *  - is_active = false: always blocked
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Domain Check: prevent cross-domain access
        $host = request()->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);

        if ($host !== $mainDomain && $host !== '127.0.0.1' && $host !== 'localhost') {
            $client = $this->client;
            if (!$client || $client->custom_domain !== $host) {
                return false;
            }
        }

        // ── Plan Expiry enforcement ────────────────────
        $client = $this->client;

        // Staff: completely blocked when plan expired
        if ($this->isStaff()) {
            if (!$client || $client->isPlanExpired()) {
                return false; // Staff cannot login at all
            }
        }

        // Seller: allowed to login (they'll see the renewal banner)
        return true;
    }

    /**
     * Super Admin কিনা চেক করে (role দিয়ে)
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function hasStaffPermission(string $permission): bool
    {
        if ($this->isSuperAdmin())
            return true;
        if ($this->isStaff()) {
            return in_array($permission, $this->staff_permissions ?? []);
        }
        return true; // regular seller has all permissions for their own shop
    }

    /**
     * Relationship with Client
     * Seller owns the client (hasOne). Staff belongs to the client (belongsTo).
     */
    public function client()
    {
        // For 'staff', client_id exists on the User model
        if ($this->role === 'staff') {
            return $this->belongsTo(Client::class , 'client_id');
        }

        // For 'seller' or 'admin', client is connected via user_id
        return $this->hasOne(Client::class , 'user_id');
    }

    /**
     * Helper to reliably get the client for the user, regardless of role.
     * This makes it easy to just do $user->activeClient()->id anywhere.
     */
    public function getActiveClientAttribute()
    {
        return $this->client;
    }

    /**
     * Boot method for model events
     */
    protected static function booted()
    {
        // 🛑 [BUG FIX]: ডাবল শপ তৈরি হওয়া ঠেকাতে এখান থেকে অটো-শপ ক্রিয়েট কোডটি মুছে দেওয়া হলো।
        // এখন শুধুমাত্র CustomRegister (Filament) থেকেই শপ তৈরি হবে, 
        // যাতে সেলার রেজিস্ট্রেশনের সময় যে নাম দিবে, ঠিক সেই নামেই ১টি মাত্র শপ তৈরি হয়।

        static::created(function ($user) {
        // ভবিষ্যতে যদি অন্য কোনো ইউজার ইভেন্ট যুক্ত করতে চান, তবে এখানে করবেন।
        });
    }

    /**
     * Send the password reset notification.
     * Overrides default to use our beautiful custom template.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CustomResetPassword($token));
    }
}