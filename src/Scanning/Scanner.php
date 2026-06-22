<?php

namespace LaravelSecurityAudit\MailGuard\Scanning;

use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use Throwable;

class Scanner
{
    /**
     * @param  iterable<Rule>  $rules
     * @param  array<string, string>  $severityOverrides  rule id => severity value
     * @param  list<string>  $suppress  rule ids to drop entirely
     */
    public function __construct(
        private readonly iterable $rules,
        private readonly array $severityOverrides = [],
        private readonly array $suppress = [],
    ) {}

    /**
     * @return list<Finding>
     */
    public function scan(MessageContext $context): array
    {
        $findings = [];

        foreach ($this->rules as $rule) {
            try {
                foreach ($rule->scan($context) as $finding) {
                    if (in_array($finding->ruleId, $this->suppress, true)) {
                        continue;
                    }

                    $findings[] = $this->applyOverride($finding);
                }
            } catch (Throwable $e) {
                // A broken rule must never stop a send or hide other findings.
                $findings[] = new Finding(
                    'scan.rule_error',
                    Severity::Info,
                    Confidence::Low,
                    'Rule failed to run',
                    $rule->id().': '.$e->getMessage(),
                    'headers',
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<Finding>  $findings
     */
    public function riskLevel(array $findings): string
    {
        $maxRank = 0;
        $value = 'ok';

        foreach ($findings as $finding) {
            if ($finding->severity->rank() > $maxRank) {
                $maxRank = $finding->severity->rank();
                $value = $finding->severity->value;
            }
        }

        return $value;
    }

    private function applyOverride(Finding $finding): Finding
    {
        $override = $this->severityOverrides[$finding->ruleId] ?? null;

        if (is_string($override) && ($severity = Severity::tryFrom($override)) !== null) {
            return $finding->withSeverity($severity);
        }

        return $finding;
    }
}
