<?php

namespace LaravelSecurityAudit\MailGuard\Scanning\Rules\Secrets;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MasksSecrets;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;

class StripeKeyRule implements Rule
{
    use MasksSecrets;

    public function id(): string
    {
        return 'secrets.stripe_key';
    }

    public function scan(MessageContext $context): iterable
    {
        if (preg_match('/\b(?:sk|rk)_live_[A-Za-z0-9]{16,}\b/', $context->body(), $matches) === 1) {
            yield new Finding(
                $this->id(),
                Severity::Critical,
                Confidence::High,
                'Stripe live secret key',
                'A live Stripe secret key (sk_live or rk_live) was found in the message.',
                'body',
                $this->mask($matches[0]),
                ['match' => $matches[0]],
            );
        }
    }
}
