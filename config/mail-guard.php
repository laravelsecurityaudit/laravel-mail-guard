<?php

use LaravelSecurityAudit\MailGuard\Scanning\Rules\Compliance\ListUnsubscribeRule;
use LaravelSecurityAudit\MailGuard\Scanning\Rules\Pii\CreditCardRule;
use LaravelSecurityAudit\MailGuard\Scanning\Rules\Privacy\TrackingPixelRule;
use LaravelSecurityAudit\MailGuard\Scanning\Rules\Secrets\PrivateKeyRule;
use LaravelSecurityAudit\MailGuard\Scanning\Rules\Secrets\StripeKeyRule;

return [
    /*
     * Capture and the inbox UI. Defaults to on outside production. This is
     * separate from guard mode below.
     */
    'enabled' => env('MAIL_GUARD_ENABLED', ! app()->isProduction()),

    'path' => trim((string) env('MAIL_GUARD_PATH', 'mail-guard'), '/'),

    'middleware' => ['web'],

    'gate' => env('MAIL_GUARD_GATE'),

    /*
     * When no gate is set, the inbox is served only in these environments.
     * Anywhere else it returns a 403. Keeps the inbox closed by default on
     * staging and other reachable, non-local environments.
     */
    'unguarded_environments' => ['local'],

    'table' => env('MAIL_GUARD_TABLE', 'mail_guard_messages'),

    'findings_table' => env('MAIL_GUARD_FINDINGS_TABLE', 'mail_guard_findings'),

    'per_page' => (int) env('MAIL_GUARD_PER_PAGE', 20),

    /*
     * Captured messages older than this are removed by the scheduled
     * "model:prune" command. Set to 0 to disable.
     */
    'retention_days' => (int) env('MAIL_GUARD_RETENTION_DAYS', 7),

    'scan' => [
        'max_bytes' => (int) env('MAIL_GUARD_MAX_BYTES', 512000),

        /*
         * Rule class => true | false. Register your own rules here; any class
         * implementing the Rule contract works.
         */
        'rules' => [
            PrivateKeyRule::class => true,
            StripeKeyRule::class => true,
            CreditCardRule::class => true,
            ListUnsubscribeRule::class => true,
            TrackingPixelRule::class => true,
        ],

        // Override a rule's severity, keyed by rule id, e.g. 'compliance.list_unsubscribe' => 'info'.
        'severity' => [],

        // Rule ids to drop entirely.
        'suppress' => [],
    ],

    'redaction' => [
        'enabled' => env('MAIL_GUARD_REDACT', true),
    ],

    'guard' => [
        /*
         * Block an unsafe send before it leaves the app. Off by default. This
         * is the part you opt into, often in production.
         */
        'enabled' => env('MAIL_GUARD_BLOCK', false),

        // null = all environments, or restrict, e.g. ['production'].
        'environments' => null,

        'min_severity' => env('MAIL_GUARD_BLOCK_MIN_SEVERITY', 'critical'),

        'min_confidence' => env('MAIL_GUARD_BLOCK_MIN_CONFIDENCE', 'high'),

        // If the scanner errors, allow the send rather than blocking it.
        'fail_open' => env('MAIL_GUARD_FAIL_OPEN', true),

        // Mailable or Notification classes that are never blocked.
        'allow_source' => [],
    ],
];
