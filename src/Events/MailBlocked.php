<?php

namespace LaravelSecurityAudit\MailGuard\Events;

use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\SecretScanner\Scanning\Finding;

class MailBlocked
{
    /**
     * @param  list<Finding>  $findings  the findings that triggered the block
     */
    public function __construct(
        public readonly MessageContext $context,
        public readonly array $findings,
    ) {}
}
