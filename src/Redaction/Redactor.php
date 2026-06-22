<?php

namespace LaravelSecurityAudit\MailGuard\Redaction;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;

class Redactor
{
    /**
     * Replace high-confidence critical matches in a stored body. Operates only
     * on the copy we persist; it never touches the real outgoing message.
     *
     * @param  list<Finding>  $findings
     */
    public function redact(?string $body, array $findings): ?string
    {
        if (! is_string($body) || $body === '') {
            return $body;
        }

        foreach ($findings as $finding) {
            if (! $this->shouldRedact($finding)) {
                continue;
            }

            $match = $finding->rawMatch();

            if ($match === null || $match === '') {
                continue;
            }

            $body = str_replace($match, '[redacted:'.$finding->ruleId.']', $body);
        }

        return $body;
    }

    private function shouldRedact(Finding $finding): bool
    {
        return $finding->severity === Severity::Critical
            && $finding->confidence === Confidence::High;
    }
}
