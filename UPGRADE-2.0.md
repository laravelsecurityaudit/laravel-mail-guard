# Upgrading mail-guard 1.x to 2.0

2.0 moves the detection engine out of mail-guard into a shared package,
`laravelsecurityaudit/laravel-secret-scanner`, so the same engine powers the
other Guard packages. Behaviour, the bundled rules, and their ids are unchanged.

## Who needs to do anything

- If you use mail-guard as-is, with no custom rules: nothing. Run `composer update` and you are done. The new core is pulled in automatically.
- If you wrote a custom rule, or you reference the scanning value objects directly (`Finding`, `Severity`, `Confidence`, `Scanner`, `Redactor`, `GuardDecision`, the `MasksSecrets` trait, or the `Rule` contract): update the namespaces as below.

## What moved

These classes moved from `LaravelSecurityAudit\MailGuard\Scanning\*` (and `...\Redaction`, `...\Guard`) to `LaravelSecurityAudit\SecretScanner\*`:

| 1.x | 2.0 |
| --- | --- |
| `MailGuard\Scanning\Contracts\Rule` | `SecretScanner\Scanning\Contracts\Rule` |
| `MailGuard\Scanning\Finding` | `SecretScanner\Scanning\Finding` |
| `MailGuard\Scanning\Severity` | `SecretScanner\Scanning\Severity` |
| `MailGuard\Scanning\Confidence` | `SecretScanner\Scanning\Confidence` |
| `MailGuard\Scanning\MasksSecrets` | `SecretScanner\Scanning\MasksSecrets` |
| `MailGuard\Scanning\Scanner` | `SecretScanner\Scanning\Scanner` |
| `MailGuard\Redaction\Redactor` | `SecretScanner\Redaction\Redactor` |
| `MailGuard\Guard\GuardDecision` | `SecretScanner\Guard\GuardDecision` |
| `MailGuard\Scanning\Rules\Secrets\PrivateKeyRule` | `SecretScanner\Rules\Secrets\PrivateKeyRule` |
| `MailGuard\Scanning\Rules\Secrets\StripeKeyRule` | `SecretScanner\Rules\Secrets\StripeKeyRule` |
| `MailGuard\Scanning\Rules\Pii\CreditCardRule` | `SecretScanner\Rules\Pii\CreditCardRule` |

`MessageContext` stays in `LaravelSecurityAudit\MailGuard\Scanning\MessageContext`. The mail-only rules (`TrackingPixelRule`, `ListUnsubscribeRule`) stay in mail-guard.

## The one behavioural change for custom rules

The `Rule` contract now receives a channel-neutral `ScanContext` instead of a `MessageContext`, so one rule can run on mail and on other channels. `MessageContext` implements `ScanContext`.

Before (1.x):

```php
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;

class MyRule implements Rule
{
    public function scan(MessageContext $context): iterable { /* ... */ }
}
```

After (2.0):

```php
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\SecretScanner\Scanning\Contracts\Rule;
use LaravelSecurityAudit\SecretScanner\Scanning\Contracts\ScanContext;

class MyRule implements Rule
{
    public function scan(ScanContext $context): iterable
    {
        // If your rule needs mail-only accessors (html(), headers(), imageTags()):
        if (! $context instanceof MessageContext) {
            return;
        }

        // ... use $context->body() for channel-neutral text, or the mail accessors after the guard.
    }
}
```

If your rule only reads `$context->body()`, the `instanceof` guard is unnecessary; just change the imports and the parameter type.

## If you published the config

If you ran `vendor:publish --tag=mail-guard-config` in 1.x, your `config/mail-guard.php` lists the bundled rule classes under `scan.rules`. In 2.0 those classes live in `laravel-secret-scanner`. Re-publish the config, or update the three generic rule references to `LaravelSecurityAudit\SecretScanner\Rules\...`. The mail-only rules (`TrackingPixelRule`, `ListUnsubscribeRule`) are unchanged.

## Requirements

2.0 still targets PHP 8.2+ and Laravel 11, 12, or 13. It now also requires `laravelsecurityaudit/laravel-secret-scanner: ^0.1`, pulled in automatically.
