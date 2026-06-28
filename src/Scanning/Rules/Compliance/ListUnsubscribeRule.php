<?php

namespace LaravelSecurityAudit\MailGuard\Scanning\Rules\Compliance;

use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\SecretScanner\Scanning\Confidence;
use LaravelSecurityAudit\SecretScanner\Scanning\Contracts\Rule;
use LaravelSecurityAudit\SecretScanner\Scanning\Contracts\ScanContext;
use LaravelSecurityAudit\SecretScanner\Scanning\Finding;
use LaravelSecurityAudit\SecretScanner\Scanning\Severity;

class ListUnsubscribeRule implements Rule
{
    public function id(): string
    {
        return 'compliance.list_unsubscribe';
    }

    public function scan(ScanContext $context): iterable
    {
        if (! $context instanceof MessageContext) {
            return;
        }

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
