<?php

namespace LaravelSecurityAudit\MailGuard\Tests\Unit;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Rules\Secrets\PrivateKeyRule;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class PrivateKeyRuleTest extends TestCase
{
    public function test_it_flags_a_private_key_block(): void
    {
        $body = "intro\n-----BEGIN PRIVATE KEY-----\nMIIBVgIBADANBgkqABCDEF==\n-----END PRIVATE KEY-----\noutro";
        $context = new MessageContext((new Email)->text($body));

        $findings = iterator_to_array((new PrivateKeyRule)->scan($context));

        $this->assertCount(1, $findings);
        $this->assertSame(Severity::Critical, $findings[0]->severity);
        $this->assertSame(Confidence::High, $findings[0]->confidence);
        $this->assertStringContainsString('PRIVATE KEY', (string) $findings[0]->rawMatch());
    }

    public function test_it_ignores_a_message_without_a_key(): void
    {
        $context = new MessageContext((new Email)->text('Just a normal message.'));

        $this->assertSame([], iterator_to_array((new PrivateKeyRule)->scan($context)));
    }
}
