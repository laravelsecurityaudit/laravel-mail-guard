# Competitive landscape

As of June 2026. A scan of Packagist and the web for packages that overlap with Mail Guard, and where Mail Guard sits relative to them.

## Verdict

No Laravel or PHP package was found that does the same job: scanning rendered outgoing mail at send time for secrets, PII, and compliance, then redacting, blocking the send, and failing CI. That specific combination is currently unclaimed. The adjacent space is real but solves different problems.

## Method

Packagist was searched with five queries (`mail security`, `mail secrets`, `mail guard`, `email pii`, `mailable scan`), plus general and GitHub-focused web searches. Findings below are grouped by the job each tool actually does.

## Landscape

| Package or tool | What it does | How it differs from Mail Guard |
| --- | --- | --- |
| promptphp/intercept-pii-redactor | Detects and redacts, masks, logs, or blocks email, phone, card, IP, API keys, and bearer tokens | Same detect-and-act pattern, but on AI prompts before they reach an LLM, not on outgoing mail. Closest analog, different channel. |
| stefanzweifel/laravel-sends | Tracks outgoing emails and can store their content | Observability for sent mail. No scanning, no findings, no send-time block. Owns the capture half only. |
| Eljakani/ward | Laravel security scanner with a TUI, finds misconfig and exposed secrets in code, config, and .env | Static analysis of source and configuration. Does not see rendered mail or runtime data. |
| laramint/laravel-security-scanner | Static rules for SQL injection, mass assignment, debug leaks, mail header injection, and more | Static code scanner. Complementary to a runtime mail scanner, not the same layer. |
| Privacy Filter, finitefield-org/mask-pii, oihana/php-masking, ashallendesign/redactable-models | Detect or mask PII in arbitrary text or database fields | Building blocks for PII handling. Not mail-aware, no send-time guard, no CI gate. Candidates to integrate for a stronger PII rule. |
| Mailpit, Laravel Telescope, themsaid/laravel-mail-preview | Capture and preview outgoing mail locally | Answer what an email looks like. No security analysis or blocking. Different job, crowded space. |
| emailsherlock/email-guard, willvincent/laravel-email-verifier, disposable-email guards | Validate recipient addresses (syntax, MX, disposable) | Address quality, not message content. Unrelated problem. |

## What Mail Guard owns

The defensible combination is narrow and unclaimed:

1. Outgoing mail channel, scanned at `MessageSending` on the rendered message.
2. Block before send, not detect after the fact.
3. A CI gate (`mail-guard:scan` with SARIF) that fails the build before a leak ships.

Detect-and-redact on its own is not novel: intercept-pii-redactor does it for AI prompts, and several libraries mask PII in text or data. The moat is applying it to the mail channel with a send-time guard and a CI gate, plus an inbox for review.

## Positioning guidance

- Lead with "outgoing mail" and "block before send." Avoid leading with "security scanner," since static scanners (ward, laramint) already hold that phrase.
- Frame Mail Guard as complementary: it sits next to Mailpit or Telescope (preview), laravel-sends (tracking), and a SAST (static analysis), not in competition with them.
- For advanced PII, consider an optional integration with a dedicated detector (for example Privacy Filter) rather than competing on detection accuracy.

## Naming notes

- anto0102/mailguard exists but is a Flarum extension for registration validation, a different ecosystem.
- emailsherlock/email-guard is address validation.
- The namespaced name `laravelsecurityaudit/laravel-mail-guard` is distinct and unambiguous on Packagist.

## Sources

- https://packagist.org/packages/promptphp/intercept-pii-redactor
- https://github.com/stefanzweifel/laravel-sends
- https://github.com/Eljakani/ward
- https://packagist.org/packages/laramint/laravel-security-scanner
- https://laravel-news.com/privacy-filter-detect-pii-in-text-from-laravel
- https://packagist.org/search.json?q=mail%20guard
