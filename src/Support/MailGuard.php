<?php

namespace LaravelSecurityAudit\MailGuard\Support;

use LaravelSecurityAudit\MailGuard\Models\Finding;
use PHPUnit\Framework\Assert;

/**
 * Test-time assertions over captured mail findings.
 */
class MailGuard
{
    public static function assertNoFindings(): void
    {
        $count = Finding::query()->count();

        Assert::assertSame(0, $count, "Expected no mail findings, found {$count}.");
    }

    public static function assertNoCriticalFindings(): void
    {
        $rules = Finding::query()->where('severity', 'critical')->pluck('rule_id')->all();

        Assert::assertSame([], $rules, 'Expected no critical mail findings, found: '.implode(', ', $rules));
    }

    public static function assertFlagged(string $ruleId): void
    {
        Assert::assertTrue(
            Finding::query()->where('rule_id', $ruleId)->exists(),
            "Expected a finding for rule [{$ruleId}], none found.",
        );
    }

    public static function assertNotFlagged(string $ruleId): void
    {
        Assert::assertFalse(
            Finding::query()->where('rule_id', $ruleId)->exists(),
            "Did not expect a finding for rule [{$ruleId}].",
        );
    }
}
