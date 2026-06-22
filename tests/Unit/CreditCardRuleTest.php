<?php

namespace LaravelSecurityAudit\MailGuard\Tests\Unit;

use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Rules\Pii\CreditCardRule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class CreditCardRuleTest extends TestCase
{
    public function test_it_flags_a_luhn_valid_card(): void
    {
        $context = new MessageContext((new Email)->text('Your card 4242 4242 4242 4242 was charged.'));

        $findings = iterator_to_array((new CreditCardRule)->scan($context));

        $this->assertCount(1, $findings);
        $this->assertSame('pii.credit_card', $findings[0]->ruleId);
    }

    public function test_it_ignores_a_luhn_invalid_number(): void
    {
        $context = new MessageContext((new Email)->text('Order 4242 4242 4242 4241 confirmed.'));

        $findings = iterator_to_array((new CreditCardRule)->scan($context));

        $this->assertSame([], $findings);
    }
}
