<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    // ── Helpers ────────────────────────────────
    public function isOpen(): bool       { return $this->status === 'open'; }
    public function isResolved(): bool   { return $this->status === 'resolved'; }

    public function badgeColor(): string
    {
        return match($this->status) {
            'open'        => 'warning',
            'in_progress' => 'info',
            'resolved'    => 'success',
            'closed'      => 'gray',
            default       => 'gray',
        };
    }

    public function typeBadgeColor(): string
    {
        return match($this->type) {
            'bug'       => 'danger',
            'feature'   => 'info',
            'complaint' => 'warning',
            'general'   => 'gray',
            default     => 'gray',
        };
    }
}
