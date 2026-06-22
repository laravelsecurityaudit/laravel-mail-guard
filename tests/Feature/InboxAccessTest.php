<?php

namespace LaravelSecurityAudit\MailGuard\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use LaravelSecurityAudit\MailGuard\Tests\TestCase;

class InboxAccessTest extends TestCase
{
    public function test_inbox_is_accessible_in_an_unguarded_environment(): void
    {
        $this->runPackageMigrations();

        $this->get(route('mail-guard.index'))->assertOk();
    }

    public function test_inbox_is_forbidden_outside_unguarded_environments_without_a_gate(): void
    {
        config(['mail-guard.unguarded_environments' => ['local']]);

        $this->get(route('mail-guard.index'))->assertForbidden();
    }

    public function test_a_configured_gate_blocks_the_inbox(): void
    {
        config(['mail-guard.gate' => 'viewMailGuard']);
        Gate::define('viewMailGuard', fn (): bool => false);

        $this->get(route('mail-guard.index'))->assertForbidden();
    }
}
