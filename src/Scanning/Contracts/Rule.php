<?php

namespace LaravelSecurityAudit\MailGuard\Scanning\Contracts;

use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;

interface Rule
{
    /**
     * Stable identifier, e.g. "secrets.stripe_key".
     */
    public function id(): string;

    /**
     * @return iterable<Finding>
     */
    public function scan(MessageContext $context): iterable;
}
