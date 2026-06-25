<?php

namespace LaravelSecurityAudit\MailGuard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $mailer
 * @property string|null $subject
 * @property string|null $source
 * @property string|null $sender
 * @property string|null $recipients
 * @property string|null $cc
 * @property string|null $bcc
 * @property string|null $reply_to
 * @property array<int, string>|null $to_addresses
 * @property string|null $html_body
 * @property string|null $text_body
 * @property string|null $headers
 * @property array<int, mixed>|null $attachments
 * @property string $risk_level
 * @property int $findings_count
 * @property bool $blocked
 * @property Carbon|null $captured_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Finding> $findings
 */
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
