<?php

namespace LaravelSecurityAudit\MailGuard\Scanning\Rules\Secrets;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;

class PrivateKeyRule implements Rule
{
    public function id(): string
    {
        return 'secrets.private_key';
    }

    public function scan(MessageContext $context): iterable
    {
        $pattern = '/-----BEGIN (?:[A-Z0-9 ]+ )?PRIVATE KEY-----.*?-----END (?:[A-Z0-9 ]+ )?PRIVATE KEY-----/s';

        if (preg_match($pattern, $context->body(), $matches) === 1) {
            $block = $matches[0];
            $firstLine = (string) strtok($block, "\n");

            yield new Finding(
                $this->id(),
                Severity::Critical,
                Confidence::High,
                'Private key in email body',
                'A PEM private key block was found in the message. Private keys must never be sent by email.',
                'body',
                $firstLine.' ...',
                ['match' => $block],
            );
        }
    }
}
