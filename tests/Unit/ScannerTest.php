<?php

namespace LaravelSecurityAudit\MailGuard\Tests\Unit;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Scanner;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class ScannerTest extends TestCase
{
    private function context(): MessageContext
    {
        return new MessageContext((new Email)->text('body'));
    }

    private function rule(string $id, Severity $severity): Rule
    {
        return new class($id, $severity) implements Rule
        {
            public function __construct(private string $ruleId, private Severity $severity) {}

            public function id(): string
            {
                return $this->ruleId;
            }

            public function scan(MessageContext $context): iterable
            {
                yield new Finding($this->ruleId, $this->severity, Confidence::High, 'Test', 'detail');
            }
        };
    }

    public function test_it_collects_findings_and_computes_risk_level(): void
    {
        $scanner = new Scanner([
            $this->rule('a.warning', Severity::Warning),
            $this->rule('b.critical', Severity::Critical),
        ]);

        $findings = $scanner->scan($this->context());

        $this->assertCount(2, $findings);
        $this->assertSame('critical', $scanner->riskLevel($findings));
    }

    public function test_it_returns_ok_when_there_are_no_findings(): void
    {
        $this->assertSame('ok', (new Scanner([]))->riskLevel([]));
    }

    public function test_it_suppresses_configured_rule_ids(): void
    {
        $scanner = new Scanner([$this->rule('noisy.rule', Severity::Warning)], [], ['noisy.rule']);

        $this->assertSame([], $scanner->scan($this->context()));
    }

    public function test_it_applies_severity_overrides(): void
    {
        $scanner = new Scanner([$this->rule('a.warning', Severity::Warning)], ['a.warning' => 'info']);

        $findings = $scanner->scan($this->context());

        $this->assertSame(Severity::Info, $findings[0]->severity);
    }

    public function test_a_throwing_rule_is_isolated(): void
    {
        $broken = new class implements Rule
        {
            public function id(): string
            {
                return 'broken.rule';
            }

            public function scan(MessageContext $context): iterable
            {
                throw new \RuntimeException('boom');
            }
        };

        $findings = (new Scanner([$broken]))->scan($this->context());

        $this->assertCount(1, $findings);
        $this->assertSame('scan.rule_error', $findings[0]->ruleId);
    }
}
