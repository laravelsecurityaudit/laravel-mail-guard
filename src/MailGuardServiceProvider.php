<?php

namespace LaravelSecurityAudit\MailGuard;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use LaravelSecurityAudit\MailGuard\Console\ScanCommand;
use LaravelSecurityAudit\MailGuard\Listeners\GuardOutgoingEmail;
use LaravelSecurityAudit\MailGuard\Models\Message;
use LaravelSecurityAudit\MailGuard\Redaction\Redactor;
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\Scanner;

class MailGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mail-guard.php', 'mail-guard');

        $this->app->singleton(Scanner::class, fn (): Scanner => new Scanner(
            $this->resolveRules(),
            (array) config('mail-guard.scan.severity', []),
            (array) config('mail-guard.scan.suppress', []),
        ));

        $this->app->bind(Redactor::class, fn (): Redactor => new Redactor);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mail-guard');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mail-guard.php' => config_path('mail-guard.php'),
            ], 'mail-guard-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/mail-guard'),
            ], 'mail-guard-views');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'mail-guard-migrations');

            $this->commands([ScanCommand::class]);
        }

        $this->schedulePruning();

        $captureEnabled = (bool) config('mail-guard.enabled');
        $guardEnabled = (bool) config('mail-guard.guard.enabled');

        if ($captureEnabled || $guardEnabled) {
            Event::listen(MessageSending::class, GuardOutgoingEmail::class);
        }

        if ($captureEnabled) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }

    /**
     * @return list<Rule>
     */
    private function resolveRules(): array
    {
        $rules = [];

        foreach ((array) config('mail-guard.scan.rules', []) as $class => $enabled) {
            if ($enabled === false || ! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $rule = $this->app->make($class);

            if ($rule instanceof Rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    private function schedulePruning(): void
    {
        if ((int) config('mail-guard.retention_days', 0) <= 0) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('model:prune', ['--model' => [Message::class]])->daily();
        });
    }
}
