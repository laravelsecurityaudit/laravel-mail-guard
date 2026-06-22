# Laravel Mail Guard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravelsecurityaudit/laravel-mail-guard.svg?style=flat-square)](https://packagist.org/packages/laravelsecurityaudit/laravel-mail-guard)
[![Tests](https://github.com/laravelsecurityaudit/laravel-mail-guard/actions/workflows/tests.yml/badge.svg)](https://github.com/laravelsecurityaudit/laravel-mail-guard/actions/workflows/tests.yml)
[![License](https://poser.pugx.org/laravelsecurityaudit/laravel-mail-guard/license)](LICENSE)

Scan every outgoing email your Laravel app sends for leaked secrets, PII, and compliance problems. Review findings in a built-in inbox, fail your test suite when a mail would leak, and optionally block an unsafe send before it leaves the app.

Preview tools answer "what does this email look like." Mail Guard answers "is this email safe to send."

## Why this exists

A mailable can look clean in code review and still leak, because the problem is in the data interpolated at send time, not in the template. An exception notification that dumps a request payload with a password. A debug build that includes an API key. A card number echoed into a receipt. Mail Guard runs on the rendered message, so it catches what static review cannot.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require laravelsecurityaudit/laravel-mail-guard
php artisan migrate
```

The service provider is auto-discovered. Capture and the inbox are enabled outside production by default.

## How it works

Mail Guard listens to Laravel's `MessageSending` event. For each message it builds a context, runs the rule set, stores the message and its findings (with high-confidence secrets redacted), and, when guard mode is on, can stop the send.

## The inbox

In an unguarded environment, open `/mail-guard`. Each message shows a risk badge and a Security tab listing every finding with its severity and a redacted snippet. Filter the list by risk and clear it with one click.

### Protecting the inbox

Captured mail can contain sensitive data, so the inbox is closed by default unless explicitly opened:

1. If `mail-guard.gate` is set, that gate must pass for every request.
2. If no gate is set, the inbox is served only in `mail-guard.unguarded_environments` (default `['local']`). Anywhere else it returns a 403.

Recommended for staging and shared environments:

```php
// config/mail-guard.php
'middleware' => ['web', 'auth'],
'gate' => 'viewMailGuard',
```

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewMailGuard', fn ($user) => $user->isAdmin());
```

## Failing tests when mail leaks

```php
use LaravelSecurityAudit\MailGuard\Support\MailGuard;

public function test_verification_email_leaks_nothing(): void
{
    $user->sendEmailVerificationNotification();

    MailGuard::assertNoCriticalFindings();
}
```

Also available: `assertNoFindings()`, `assertFlagged($ruleId)`, and `assertNotFlagged($ruleId)`.

## Failing CI with SARIF

Run your suite so mail is captured, then scan:

```bash
php artisan test
php artisan mail-guard:scan --min-severity=critical --format=sarif --output=mail-guard.sarif
```

`mail-guard:scan` exits non-zero when any finding meets the threshold. Formats are `table`, `json`, and `sarif`. Upload SARIF to GitHub code scanning:

```yaml
- run: php artisan test
- run: php artisan mail-guard:scan --min-severity=critical --format=sarif --output=mail-guard.sarif
- uses: github/codeql-action/upload-sarif@v3
  with:
    sarif_file: mail-guard.sarif
```

Because a runtime leak does not map to a source line, SARIF results point at the originating Mailable or Notification class when known, otherwise at a synthetic Mail Guard location.

## Guard mode

Guard mode blocks a send when a finding meets the configured threshold. It is off by default and is the part you opt into, often in production.

```dotenv
MAIL_GUARD_BLOCK=true
```

Defaults are conservative: it blocks only `critical` findings at `high` confidence, and `fail_open` is true so a scanner error never silently drops mail. Bypass a known-safe send with the `X-Mail-Guard-Bypass` header or the `guard.allow_source` allowlist. Every block fires a `MailBlocked` event and a log warning with the rule ids, never the secret.

## Rules

| Rule id | Severity | Confidence | Blocks |
| --- | --- | --- | --- |
| `secrets.private_key` | critical | high | yes |
| `secrets.stripe_key` | critical | high | yes |
| `pii.credit_card` | critical | high | yes |
| `compliance.list_unsubscribe` | warning | high | no |
| `privacy.tracking_pixel` | warning | medium | no |

Toggle rules, override severities, and suppress findings in `config/mail-guard.php`. Add your own rule by implementing `LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule` and registering its class in `scan.rules`.

## Configuration

```bash
php artisan vendor:publish --tag=mail-guard-config
```

Environment variables:

```dotenv
MAIL_GUARD_ENABLED=true
MAIL_GUARD_PATH=mail-guard
MAIL_GUARD_GATE=viewMailGuard
MAIL_GUARD_RETENTION_DAYS=7
MAIL_GUARD_REDACT=true
MAIL_GUARD_BLOCK=false
```

Captured messages older than `MAIL_GUARD_RETENTION_DAYS` are removed by the scheduled `model:prune` command. Set it to 0 to disable.

## Testing

```bash
composer test
composer analyse
```

## License

The MIT License (MIT). See [LICENSE](LICENSE).
