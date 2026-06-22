<?php

namespace LaravelSecurityAudit\MailGuard\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use LaravelSecurityAudit\MailGuard\Models\Message;
use LaravelSecurityAudit\MailGuard\Tests\TestCase;

class GuardTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('mail-guard.guard.enabled', true);
    }

    public function test_it_blocks_a_message_containing_a_private_key(): void
    {
        $this->runPackageMigrations();

        $transport = Mail::mailer('array')->getSymfonyTransport();

        $body = "Deploy key:\n-----BEGIN PRIVATE KEY-----\nMIIBVgIBADANBgkqABCDEF==\n-----END PRIVATE KEY-----";
        Mail::raw($body, fn ($message) => $message->to('ops@example.com')->subject('Deploy key'));

        $this->assertCount(0, $transport->messages());

        $message = Message::query()->first();
        $this->assertNotNull($message);
        $this->assertTrue((bool) $message->blocked);
    }

    public function test_it_allows_clean_mail_through(): void
    {
        $this->runPackageMigrations();

        $transport = Mail::mailer('array')->getSymfonyTransport();

        Mail::raw('Nothing sensitive here.', fn ($message) => $message->to('user@example.com')->subject('Hello'));

        $this->assertCount(1, $transport->messages());
    }
}
