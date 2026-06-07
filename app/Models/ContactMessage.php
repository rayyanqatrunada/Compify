<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const STATUS_UNREAD   = 'unread';
    public const STATUS_READ     = 'read';
    public const STATUS_REPLIED  = 'replied';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_UNREAD,
        self::STATUS_READ,
        self::STATUS_REPLIED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'ip_address',
        'user_agent',
    ];

    /* ── Scopes ──────────────────────────────────────── */

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /* ── Helpers ─────────────────────────────────────── */

    public function isUnread(): bool
    {
        return $this->status === self::STATUS_UNREAD;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_UNREAD   => 'Belum Dibaca',
            self::STATUS_READ     => 'Dibaca',
            self::STATUS_REPLIED  => 'Dibalas',
            self::STATUS_ARCHIVED => 'Diarsipkan',
            default               => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_UNREAD   => 'badge-danger',
            self::STATUS_READ     => 'badge-info',
            self::STATUS_REPLIED  => 'badge-success',
            self::STATUS_ARCHIVED => 'badge-muted',
            default               => 'badge-muted',
        };
    }
}
