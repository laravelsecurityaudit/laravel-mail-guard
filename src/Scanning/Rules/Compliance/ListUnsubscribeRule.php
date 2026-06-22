<?php

namespace LaravelSecurityAudit\MailGuard\Scanning\Rules\Compliance;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;

class ListUnsubscribeRule implements Rule
{
    public function id(): string
    {
        return 'compliance.list_unsubscribe';
    }

    public function scan(MessageContext $context): iterable
    {
        if (! $context->hasHeader('List-Unsubscribe')) {
            yield new Finding(
                $this->id(),
                Severity::Warning,
                Confidence::High,
                'Missing List-Unsubscribe header',
                'Bulk and marketing mail should include a List-Unsubscribe header, ideally with one-click List-Unsubscribe-Post. Override the severity to info for purely transactional mail.',
                'headers',
            );
        }
    }
}
