<?php

namespace LaravelSecurityAudit\MailGuard\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use LaravelSecurityAudit\MailGuard\Models\Message;
use LaravelSecurityAudit\MailGuard\Support\MailGuard;
use LaravelSecurityAudit\MailGuard\Tests\TestCase;

class CaptureTest extends TestCase
{
    public function test_a_stripe_key_is_captured_flagged_and_redacted(): void
    {
        $this->runPackageMigrations();

        Mail::raw('Here is sk_live_0123456789abcdefXYZ for staging.', function ($message): void {
            $message->to('reader@example.com')->subject('Credentials');
        });

        $message = Message::query()->first();

        $this->assertNotNull($message);
        $this->assertSame('critical', $message->risk_level);
        $this->assertSame('reader@example.com', $message->to_addresses[0]['address']);
        $this->assertStringNotContainsString('sk_live_0123456789abcdefXYZ', (string) $message->text_body);
        MailGuard::assertFlagged('secrets.stripe_key');
    }

    public function test_clean_mail_records_no_critical_findings(): void
    {
        $this->runPackageMigrations();

        Mail::raw('Welcome aboard, nothing sensitive here.', function ($message): void {
            $message->to('reader@example.com')->subject('Welcome');
        });

        $message = Message::query()->first();

        $this->assertNotNull($message);
        $this->assertNotSame('critical', $message->risk_level);
        MailGuard::assertNoCriticalFindings();
    }

    public function test_the_inbox_lists_captured_messages(): void
    {
        $this->runPackageMigrations();

        Mail::raw('Body', fn ($message) => $message->to('reader@example.com')->subject('Subject line'));

        $this->get(route('mail-guard.index'))
            ->assertOk()
            ->assertSee('Mail Guard')
            ->assertSee('Subject line');
    }
}
