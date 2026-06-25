<?php

namespace LaravelSecurityAudit\MailGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $message_id
 * @property string $rule_id
 * @property string $severity
 * @property string $confidence
 * @property string $title
 * @property string|null $detail
 * @property string|null $location
 * @property string|null $snippet
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property-read Message $message
 */
class Finding extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('mail-guard.findings_table', 'mail_guard_findings');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
