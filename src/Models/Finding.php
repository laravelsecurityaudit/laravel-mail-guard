<?php

namespace LaravelSecurityAudit\MailGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
