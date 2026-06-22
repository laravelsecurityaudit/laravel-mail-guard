<?php

namespace LaravelSecurityAudit\MailGuard\Scanning;

trait MasksSecrets
{
    /**
     * Produce a display snippet that proves a match without revealing the value.
     */
    protected function mask(string $value): string
    {
        $length = strlen($value);

        if ($length <= 8) {
            return str_repeat('*', max(1, $length));
        }

        return substr($value, 0, 4).str_repeat('*', $length - 6).substr($value, -2);
    }
}
