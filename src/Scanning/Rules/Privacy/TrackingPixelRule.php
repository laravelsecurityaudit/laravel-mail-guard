<?php

namespace LaravelSecurityAudit\MailGuard\Scanning\Rules\Privacy;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;

class TrackingPixelRule implements Rule
{
    public function id(): string
    {
        return 'privacy.tracking_pixel';
    }

    public function scan(MessageContext $context): iterable
    {
        foreach ($context->imageTags() as $tag) {
            if ($this->looksLikePixel($tag)) {
                yield new Finding(
                    $this->id(),
                    Severity::Warning,
                    Confidence::Medium,
                    'Tracking pixel detected',
                    'A 1x1 or hidden image was found, which is commonly used for open tracking.',
                    'html',
                    $this->trim($tag),
                );
            }
        }
    }

    private function looksLikePixel(string $tag): bool
    {
        $tag = strtolower($tag);

        $tinyWidth = preg_match('/\bwidth\s*=\s*["\']?\s*[01]\s*(?:px)?["\']?/', $tag) === 1;
        $tinyHeight = preg_match('/\bheight\s*=\s*["\']?\s*[01]\s*(?:px)?["\']?/', $tag) === 1;

        if ($tinyWidth && $tinyHeight) {
            return true;
        }

        if (str_contains($tag, 'display:none') || str_contains($tag, 'visibility:hidden')) {
            return true;
        }

        return preg_match('/width\s*:\s*[01]px/', $tag) === 1
            && preg_match('/height\s*:\s*[01]px/', $tag) === 1;
    }

    private function trim(string $tag): string
    {
        return strlen($tag) > 140 ? substr($tag, 0, 140).' ...' : $tag;
    }
}
