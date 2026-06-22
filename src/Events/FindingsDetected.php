<?php

namespace LaravelSecurityAudit\MailGuard\Events;

use LaravelSecurityAudit\MailGuard\Models\Message;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;

class FindingsDetected
{
    /**
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public readonly Message $message,
        public readonly array $findings,
    ) {}
}
