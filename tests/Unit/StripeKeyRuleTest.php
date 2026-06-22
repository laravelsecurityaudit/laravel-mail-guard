<?php

namespace LaravelSecurityAudit\MailGuard\Tests\Unit;

use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Rules\Secrets\StripeKeyRule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class StripeKeyRuleTest extends TestCase
{
    public function test_it_flags_a_live_secret_key(): void
    {
        $context = new MessageContext((new Email)->text('key: sk_live_0123456789abcdefXYZ'));

        $findings = iterator_to_array((new StripeKeyRule)->scan($context));

        $this->assertCount(1, $findings);
        $this->assertSame('secrets.stripe_key', $findings[0]->ruleId);
        $this->assertStringNotContainsString('sk_live_0123456789abcdefXYZ', (string) $findings[0]->snippet);
    }

    public function test_it_ignores_a_test_key(): void
    {
        $context = new MessageContext((new Email)->text('key: sk_test_0123456789abcdefXYZ'));

        $findings = iterator_to_array((new StripeKeyRule)->scan($context));

        $this->assertSame([], $findings);
    }
}
