<?php

namespace LaravelSecurityAudit\MailGuard\Events;

use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;

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
