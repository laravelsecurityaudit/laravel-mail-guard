# Laravel Mail Guard

<p>
    <a href="https://github.com/laravelsecurityaudit/laravel-mail-guard/actions"><img src="https://github.com/laravelsecurityaudit/laravel-mail-guard/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
    <a href="https://packagist.org/packages/laravelsecurityaudit/laravel-mail-guard"><img src="https://img.shields.io/packagist/v/laravelsecurityaudit/laravel-mail-guard?style=flat-square" alt="Latest version on Packagist"></a>
    <a href="https://packagist.org/packages/laravelsecurityaudit/laravel-mail-guard"><img src="https://img.shields.io/packagist/php-v/laravelsecurityaudit/laravel-mail-guard?style=flat-square" alt="PHP version"></a>
    <a href="LICENSE"><img src="https://img.shields.io/packagist/l/laravelsecurityaudit/laravel-mail-guard?style=flat-square" alt="License"></a>
</p>

Scan every outgoing email your Laravel app sends for leaked secrets, PII and compliance problems. Review findings in a built in inbox, fail your test suite when a mail would leak, and optionally block an unsafe send before it leaves the app.

Preview tools answer "what does this email look like". Mail Guard answers "is this email safe to send".

> This is an independent open source package. It is not affiliated with, endorsed by, or sponsored by Laravel or Laravel LLC.

## Contents

- [Why this exists](#why-this-exists)
- [Where Mail Guard fits](#where-mail-guard-fits)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quickstart](#quickstart)
- [How it works](#how-it-works)
- [What it detects](#what-it-detects)
- [The inbox](#the-inbox)
- [Failing tests when mail leaks](#failing-tests-when-mail-leaks)
- [The scan command](#the-scan-command)
- [Continuous integration](#continuous-integration)
- [Guard mode](#guard-mode)
- [What gets stored](#what-gets-stored)
- [Events](#events)
- [Configuration](#configuration)
- [Writing your own rule](#writing-your-own-rule)
- [What this package does not do](#what-this-package-does-not-do)
- [Testing](#testing)
- [The Laravel Security Audit family](#the-laravel-security-audit-family)

## Why this exists

A mailable can look clean in review and still leak, because the problem is in the data interpolated at send time, not in the template.

```blade
{{-- Reviewed. Approved. Fine. --}}
<p>Something went wrong processing your request:</p>
<pre>{{ $exception->getMessage() }}</pre>
```

Six months later a payment library starts including the request payload in its exception messages, and your error notification mails a live Stripe key to whoever triggered it. Nothing in that diff changed. Nothing in that template is wrong.

The same shape covers the rest of the category: a debug build that echoes config into a footer, a receipt that prints a full card number because the masking helper was skipped, an internal report forwarded to a customer address.

Mail Guard runs on the rendered message, which is the first point where the real values exist.

## Where Mail Guard fits

It is a runtime, mail specific layer that sits alongside what you already use rather than replacing it.

| Tool | Answers | Mail Guard adds |
| --- | --- | --- |
| Mailpit, Telescope | what does this email look like | whether it is safe, and stops it |
| Outgoing mail trackers | what was sent | content inspection before delivery |
| Static analyzers | what is in the source and `.env` | what is in the rendered message |
| PII and masking libraries | how to mask a value | redaction at rest, a send time guard, a CI gate |

The combination it owns: outgoing mail, blocked before send, gated in CI.

## Requirements

- PHP 8.2, 8.3 or 8.4
- Laravel 12 or 13

## Installation

```bash
composer require laravelsecurityaudit/laravel-mail-guard
php artisan migrate
```

The service provider is auto discovered. Capture and the inbox are on outside production by default; blocking is off until you turn it on.

Detection comes from [`laravelsecurityaudit/laravel-secret-scanner`](https://packagist.org/packages/laravelsecurityaudit/laravel-secret-scanner), pulled in automatically.

## Quickstart

Install, migrate, then run your app or your test suite. Every send is captured.

```bash
php artisan mail-guard:scan
```

```
+----------+------------------------------+----------------------------------+---------------------+
| Severity | Rule                         | Subject                          | Captured            |
+----------+------------------------------+----------------------------------+---------------------+
| critical | secrets.stripe_key           | Something went wrong             | 2026-08-12 09:22:41 |
| critical | pii.credit_card              | Your receipt from Acme           | 2026-08-12 09:31:08 |
| warning  | compliance.list_unsubscribe  | August product update            | 2026-08-12 10:02:55 |
+----------+------------------------------+----------------------------------+---------------------+

  ERROR  2 finding(s) at or above [critical].
```

The command exits non zero, so it gates a pipeline with no extra wiring. Open `/mail-guard` locally to read the message each finding came from, with the secret already redacted.

## How it works

Mail Guard listens to Laravel's `MessageSending` event. For each message it:

1. Builds a `MessageContext` over the Symfony `Email`, combining subject, text body and HTML body into one scan target, capped at `scan.max_bytes` (512 KB by default).
2. Runs the rule set over that text.
3. Stores the message and its findings, with critical high confidence matches redacted in the stored subject and bodies.
4. When guard mode is on and the findings cross the threshold, returns `false` from the listener, which halts delivery.

`MessageSending` is dispatched through a halting dispatcher, so a `false` return is the documented way to stop a send. Nothing is intercepted at the transport level and no mailer is wrapped.

Everything runs inside a try block. On an internal error, `fail_open` (true by default) lets the mail go out rather than dropping it over a bug in a regex. Set it to `false` and an error blocks the send instead.

## What it detects

| Rule id | Severity | Confidence | Blocks by default | Ships in |
| --- | --- | --- | --- | --- |
| `secrets.private_key` | critical | high | yes | secret-scanner |
| `secrets.stripe_key` | critical | high | yes | secret-scanner |
| `pii.credit_card` | critical | high | yes | secret-scanner |
| `compliance.list_unsubscribe` | warning | high | no | mail-guard |
| `privacy.tracking_pixel` | warning | medium | no | mail-guard |

**`compliance.list_unsubscribe`** fires when the message has no `List-Unsubscribe` header. That header is what mailbox providers look for on bulk mail, and its absence is the most common reason a legitimate campaign lands in spam. It is a warning rather than critical because a transactional password reset does not need one; suppress it if your app only sends transactional mail.

**`privacy.tracking_pixel`** finds `<img>` tags sized to disappear: a `width` or `height` attribute of `0` or `1`, or `width: 1px` together with `height: 1px` in an inline style. Useful when a marketing template arrives from outside your team and you would rather know what it phones home to.

Turn a rule off, re-rank it, or drop its findings:

```php
'scan' => [
    'rules' => [
        ListUnsubscribeRule::class => false,          // transactional only
    ],
    'severity' => ['privacy.tracking_pixel' => 'info'],
    'suppress' => ['compliance.list_unsubscribe'],
],
```

## The inbox

In an unguarded environment, open `/mail-guard`. Each message shows a risk badge and a Security tab listing every finding with its severity and a redacted snippet. Filter by risk, clear the list in one click.

Captured mail contains real customer data, so the inbox is closed by default:

1. If `mail-guard.gate` is set, that gate must pass for every request.
2. If no gate is set, the inbox is served only in `mail-guard.unguarded_environments` (default `['local']`). Anywhere else returns 403.

For staging and any other shared environment, put it behind real auth:

```php
// config/mail-guard.php
'middleware' => ['web', 'auth'],
'gate' => 'viewMailGuard',
```

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewMailGuard', fn ($user) => $user->isAdmin());
```

Messages older than `retention_days` (7 by default) are removed by the scheduled `model:prune` command, which the package registers for you. Set it to `0` to keep everything and prune yourself.

## Failing tests when mail leaks

The highest value place to run this is the suite you already have. Exercise the notification, then assert on what would have gone out.

```php
use LaravelSecurityAudit\MailGuard\Support\MailGuard;

public function test_the_failure_notification_leaks_nothing(): void
{
    $this->processPaymentThatFails();

    MailGuard::assertNoCriticalFindings();
}
```

| Assertion | Passes when |
| --- | --- |
| `MailGuard::assertNoFindings()` | no findings of any severity were captured |
| `MailGuard::assertNoCriticalFindings()` | no critical findings were captured |
| `MailGuard::assertFlagged($ruleId)` | at least one finding for that rule exists |
| `MailGuard::assertNotFlagged($ruleId)` | no finding for that rule exists |

Use `assertFlagged()` to test the guard itself: send a known bad fixture and prove the rule fires, so the protection cannot silently rot.

## The scan command

```bash
php artisan mail-guard:scan
```

| Option | Default | Description |
| --- | --- | --- |
| `--min-severity=` | `critical` | Exit non zero when a finding at or above this severity exists (`info`, `warning`, `critical`) |
| `--format=` | `table` | Output format: `table`, `json` or `sarif` |
| `--output=` | stdout | Write the report to this file instead of stdout |
| `--since=` | all time | Only consider messages captured at or after this date or time |

```bash
# Include warnings in the gate
php artisan mail-guard:scan --min-severity=warning

# Only what this CI run produced
php artisan mail-guard:scan --since="-10 minutes"
```

A runtime leak has no source line, so SARIF results point at the originating Mailable or Notification class when Laravel exposed it in the event data, and at a synthetic Mail Guard location otherwise.

## Continuous integration

Run your suite first so mail gets captured, then scan.

### GitHub Actions

```yaml
name: mail-guard

on: [push, pull_request]

jobs:
  mail:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - run: composer install --prefer-dist --no-interaction --no-progress

      - run: php artisan test

      - name: Scan captured mail
        run: php artisan mail-guard:scan --min-severity=critical --format=sarif --output=mail-guard.sarif

      - name: Upload SARIF
        if: always()
        uses: github/codeql-action/upload-sarif@v3
        with:
          sarif_file: mail-guard.sarif
```

### GitLab CI

```yaml
mail-guard:
  image: php:8.3-cli
  script:
    - composer install --prefer-dist --no-interaction --no-progress
    - php artisan test
    - php artisan mail-guard:scan --min-severity=critical --format=json --output=mail-guard.json
  artifacts:
    when: always
    paths:
      - mail-guard.json
```

## Guard mode

Guard mode blocks a send when a finding meets the threshold. Off by default, and the part you opt into.

```dotenv
MAIL_GUARD_BLOCK=true
```

Defaults are conservative: only `critical` findings at `high` confidence block, so the warning level rules never stop mail. A block fires a `MailBlocked` event and writes a log warning listing the rule ids, never the secret.

```dotenv
MAIL_GUARD_BLOCK_MIN_SEVERITY=critical
MAIL_GUARD_BLOCK_MIN_CONFIDENCE=high
MAIL_GUARD_FAIL_OPEN=true
```

```php
'guard' => [
    'environments' => ['production'],  // null = everywhere
],
```

Two escape hatches for a send you know is fine:

```php
// The originating Mailable or Notification class, never blocked.
'allow_source' => [\App\Notifications\InternalKeyRotated::class],
```

```php
// Or set the bypass header on the message itself.
$message->getHeaders()->addTextHeader('X-Mail-Guard-Bypass', '1');
```

The header name is fixed and not configurable. `allow_source` matches the class Laravel puts in the event data, which is available for Mailables and Notifications but not for every send path; a raw `Mail::raw()` has no source and cannot be allowlisted this way.

A sane rollout: capture in staging for a week, read the inbox, suppress what is only noise for your data, then enable blocking in production.

## What gets stored

Per message: the mailer, subject, source class, sender, recipients, cc, bcc, reply-to, HTML and text bodies, raw headers, attachment metadata, risk level, finding count, whether it was blocked, and the capture timestamp. Subject and both bodies are redacted before storage when `redaction.enabled` is true.

Attachments are recorded as **metadata only**: filename, content type and disposition. File contents are never stored and never scanned.

Two tables, both renameable via `MAIL_GUARD_TABLE` and `MAIL_GUARD_FINDINGS_TABLE`. Publish the migrations if you need to change them:

```bash
php artisan vendor:publish --tag=mail-guard-migrations
```

## Events

| Event | Fires when | Carries |
| --- | --- | --- |
| `FindingsDetected` | a captured message had findings | `message` (the stored model), `findings` |
| `MailBlocked` | guard mode halted a send | `context` (the `MessageContext`), `findings` (only the blocking ones) |

```php
use LaravelSecurityAudit\MailGuard\Events\MailBlocked;

Event::listen(function (MailBlocked $event) {
    Slack::to('#security')->send(
        'Blocked an outgoing email from '.($event->context->source() ?? 'an unknown source').
        ': '.collect($event->findings)->pluck('ruleId')->implode(', ')
    );
});
```

`MailBlocked` carries the context rather than a stored model, so a listener can read the subject and headers of the message that was stopped.

## Configuration

```bash
php artisan vendor:publish --tag=mail-guard-config
```

Other publish tags: `mail-guard-views` (to restyle the inbox) and `mail-guard-migrations`.

```dotenv
# Capture and inbox. Defaults to on outside production.
MAIL_GUARD_ENABLED=true
MAIL_GUARD_PATH=mail-guard
MAIL_GUARD_GATE=viewMailGuard
MAIL_GUARD_PER_PAGE=20
MAIL_GUARD_RETENTION_DAYS=7

# Storage
MAIL_GUARD_TABLE=mail_guard_messages
MAIL_GUARD_FINDINGS_TABLE=mail_guard_findings
MAIL_GUARD_MAX_BYTES=512000

# Redaction of the stored copy
MAIL_GUARD_REDACT=true

# Guard mode
MAIL_GUARD_BLOCK=false
MAIL_GUARD_BLOCK_MIN_SEVERITY=critical
MAIL_GUARD_BLOCK_MIN_CONFIDENCE=high
MAIL_GUARD_FAIL_OPEN=true
```

`middleware`, `unguarded_environments`, `guard.environments` and `guard.allow_source` have no environment variable and are set in the published config file.

## Writing your own rule

Rules implement one interface from the shared scanner. This one catches an internal customer id that must never reach a customer's inbox:

```php
namespace App\Security\Rules;

use LaravelSecurityAudit\SecretScanner\Scanning\Confidence;
use LaravelSecurityAudit\SecretScanner\Scanning\Contracts\Rule;
use LaravelSecurityAudit\SecretScanner\Scanning\Contracts\ScanContext;
use LaravelSecurityAudit\SecretScanner\Scanning\Finding;
use LaravelSecurityAudit\SecretScanner\Scanning\MasksSecrets;
use LaravelSecurityAudit\SecretScanner\Scanning\Severity;

class InternalNoteRule implements Rule
{
    use MasksSecrets;

    public function id(): string
    {
        return 'privacy.internal_note';
    }

    public function scan(ScanContext $context): iterable
    {
        if (preg_match('/\[INTERNAL\].*/', $context->body(), $matches) === 1) {
            yield new Finding(
                $this->id(),
                Severity::Critical,
                Confidence::High,
                'Internal note in outgoing mail',
                'A block marked [INTERNAL] was found in a message addressed to a customer.',
                $context->location(),
                $this->mask($matches[0]),
                ['match' => $matches[0]],
            );
        }
    }
}
```

Register it:

```php
'scan' => [
    'rules' => [
        // ...
        \App\Security\Rules\InternalNoteRule::class => true,
    ],
],
```

`$context->body()` gives you subject, text and HTML combined. The `MessageContext` also exposes `subject()`, `html()`, `text()`, `rawHeaders()`, `hasHeader()` and `imageTags()` if your rule needs one part specifically, as the compliance and privacy rules do.

## What this package does not do

- **It does not scan attachments.** A PDF invoice with a card number in it passes untouched; only its filename, content type and disposition are recorded.
- **It scans a capped body.** Subject, text and HTML are combined and truncated at `scan.max_bytes` (512 KB). A leak past that point in a very large HTML email is not seen.
- **It does not scan headers for secrets.** Headers are stored, and `List-Unsubscribe` presence is checked, but the scan target is the body and subject.
- **`allow_source` cannot cover every send.** Laravel exposes the originating class for Mailables and Notifications, not for every path into the mailer.
- **Redaction applies to the stored copy, not the sent mail.** In capture mode the email still goes out exactly as written. Blocking is what stops delivery.
- **It cannot judge intent.** It knows a card number is present, not whether this recipient is allowed to see it.
- **It is not a substitute for reviewing authorization.** Sending the right data to the wrong recipient is an access control bug, and no content scanner will catch it.

## Testing

```bash
composer test      # phpunit
composer analyse   # phpstan / larastan
composer format    # pint
```

The suite runs against PHP 8.2, 8.3 and 8.4 on Laravel 12 and 13.

## Security

Found a vulnerability in this package? See [SECURITY.md](SECURITY.md). Please do not open a public issue.

## The Laravel Security Audit family

One detection engine, guarding every place data leaves your app.

| Package | What it guards |
| --- | --- |
| [laravel-secret-scanner](https://packagist.org/packages/laravelsecurityaudit/laravel-secret-scanner) | Shared secret and PII detection engine (the core) |
| **laravel-mail-guard** (this package) | Outgoing Laravel mail |
| [laravel-ai-egress-guard](https://packagist.org/packages/laravelsecurityaudit/laravel-ai-egress-guard) | Outbound AI provider traffic |
| [laravel-ai-lint](https://packagist.org/packages/laravelsecurityaudit/laravel-ai-lint) | Static analysis: leaked AI keys and unsafe AI wiring |
| [laravel-ai-circuit-breaker](https://packagist.org/packages/laravelsecurityaudit/laravel-ai-circuit-breaker) | Runaway AI loops and spend |
| [laravel-ai-ledger](https://packagist.org/packages/laravelsecurityaudit/laravel-ai-ledger) | GDPR Article 30 processing ledger for AI traffic |

## License

The MIT License (MIT). See [LICENSE](LICENSE).
