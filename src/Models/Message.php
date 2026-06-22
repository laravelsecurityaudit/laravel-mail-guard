<?php

namespace LaravelSecurityAudit\MailGuard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use Prunable;

    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('mail-guard.table', 'mail_guard_messages');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class, 'message_id');
    }

    public function prunable(): Builder
    {
        $days = (int) config('mail-guard.retention_days', 0);

        if ($days <= 0) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('captured_at', '<=', now()->subDays($days));
    }

    protected function casts(): array
    {
        return [
            'to_addresses' => 'array',
            'attachments' => 'array',
            'findings_count' => 'integer',
            'blocked' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }
}
