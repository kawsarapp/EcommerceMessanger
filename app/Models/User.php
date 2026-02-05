<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Str;

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
     * Boot method for model events
     */
    protected static function booted()
    {
        static::created(function ($user) {
            // নতুন ইউজার রেজিস্টার করলেই তার জন্য একটি ডিফল্ট শপ তৈরি হবে
            \App\Models\Client::create([
                'user_id' => $user->id,
                'shop_name' => $user->name . "'s Shop",
                'slug' => Str::slug($user->name . "-" . rand(100, 999)),
                'status' => 'active',
            ]);
        });
    }

    /**
     * Relationship with Client
     */
    public function client()
    {
        return $this->hasOne(\App\Models\Client::class);
    }

    
}