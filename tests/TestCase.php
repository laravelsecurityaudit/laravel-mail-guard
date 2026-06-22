<?php

namespace LaravelSecurityAudit\MailGuard\Tests;

use LaravelSecurityAudit\MailGuard\MailGuardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MailGuardServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.from.address', 'sender@example.com');
        $app['config']->set('mail.from.name', 'Mail Guard');

        $app['config']->set('mail-guard.enabled', true);
        $app['config']->set('mail-guard.path', 'mail-guard');
        $app['config']->set('mail-guard.middleware', ['web']);
        $app['config']->set('mail-guard.gate', null);
        $app['config']->set('mail-guard.unguarded_environments', ['testing']);
        $app['config']->set('mail-guard.retention_days', 7);
        $app['config']->set('mail-guard.guard.enabled', false);
    }

    protected function runPackageMigrations(): void
    {
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }
}
