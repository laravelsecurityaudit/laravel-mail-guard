# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- BREAKING: the detection engine (`Scanner`, `Finding`, `Severity`, `Confidence`, `Redactor`, `GuardDecision`, the `MasksSecrets` trait, the `Rule` contract, and the generic `secrets.private_key`, `secrets.stripe_key`, and `pii.credit_card` rules) moved to the shared `laravelsecurityaudit/laravel-secret-scanner` package. Custom rules now implement `LaravelSecurityAudit\SecretScanner\Scanning\Contracts\Rule` and receive a `ScanContext` (which `MessageContext` implements) instead of a `MessageContext`. Mail-only rules and behavior are unchanged. Intended for release as 2.0.0.

## [1.0.0] - 2026-06-25

### Added

- Outgoing mail scanning via the `MessageSending` event, producing severity-ranked findings.
- Starter rule set: private keys, Stripe live keys, Luhn-validated card numbers, missing `List-Unsubscribe`, and tracking pixels.
- Redaction at rest for high-confidence critical matches.
- Guard mode that blocks an unsafe send before it leaves the application.
- Inbox UI with risk badges and a Security tab.
- Test assertions (`MailGuard::assertNoCriticalFindings()` and friends).
- `mail-guard:scan` command with table, JSON, and SARIF output for CI.
