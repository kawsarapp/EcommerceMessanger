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
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'staff_permissions'  => 'array',
            'is_active'          => 'boolean',
        ];
    }

    /**
     * Filament প্যানেল এক্সেস চেক
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // ক্লায়েন্ট এবং এডমিন দুজনেই ড্যাশবোর্ড এক্সেস পাবে
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
        if ($this->isSuperAdmin()) return true;
        if ($this->isStaff()) {
            return in_array($permission, $this->staff_permissions ?? []);
        }
        return true; // regular seller has all permissions for their own shop
    }

    /**
     * Relationship with Client
     */
    public function client()
    {
        return $this->hasOne(Client::class);
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
}