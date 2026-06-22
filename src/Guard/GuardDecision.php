<?php

namespace LaravelSecurityAudit\MailGuard\Guard;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;

class GuardDecision
{
    public function __construct(
        private readonly Severity $minSeverity,
        private readonly Confidence $minConfidence,
    ) {}

    /**
     * Findings severe and confident enough to block a send.
     *
     * @param  list<Finding>  $findings
     * @return list<Finding>
     */
    public function blocking(array $findings): array
    {
        return array_values(array_filter(
            $findings,
            fn (Finding $finding): bool => $finding->severity->atLeast($this->minSeverity)
                && $finding->confidence->atLeast($this->minConfidence),
        ));
    }

    /**
     * @param  list<Finding>  $findings
     */
    public function shouldBlock(array $findings): bool
    {
        return $this->blocking($findings) !== [];
    }
}
