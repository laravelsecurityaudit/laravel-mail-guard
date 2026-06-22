# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Outgoing mail scanning via the `MessageSending` event, producing severity-ranked findings.
- Starter rule set: private keys, Stripe live keys, Luhn-validated card numbers, missing `List-Unsubscribe`, and tracking pixels.
- Redaction at rest for high-confidence critical matches.
- Guard mode that blocks an unsafe send before it leaves the application.
- Inbox UI with risk badges and a Security tab.
- Test assertions (`MailGuard::assertNoCriticalFindings()` and friends).
- `mail-guard:scan` command with table, JSON, and SARIF output for CI.
