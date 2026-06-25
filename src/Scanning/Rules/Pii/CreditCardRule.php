<?php

namespace LaravelSecurityAudit\MailGuard\Scanning\Rules\Pii;

use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Contracts\Rule;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MasksSecrets;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;

class CreditCardRule implements Rule
{
    use MasksSecrets;

    public function id(): string
    {
        return 'pii.credit_card';
    }

    public function scan(MessageContext $context): iterable
    {
        if (preg_match_all('/\b\d(?:[ -]?\d){12,18}\b/', $context->body(), $matches) === false) {
            return;
        }

        $seen = [];

        foreach ($matches[0] as $candidate) {
            $digits = (string) preg_replace('/\D/', '', $candidate);
            $length = strlen($digits);

            if ($length < 13 || $length > 19 || ! $this->passesLuhn($digits)) {
                continue;
            }

            if (isset($seen[$digits])) {
                continue;
            }

            $seen[$digits] = true;

            yield new Finding(
                $this->id(),
                Severity::Critical,
                Confidence::High,
                'Possible payment card number',
                'A Luhn-valid card number was found in the message.',
                'body',
                $this->mask($digits),
                ['match' => $candidate],
            );
        }
    }

    private function passesLuhn(string $number): bool
    {
        $sum = 0;
        $alternate = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($alternate) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = ! $alternate;
        }

        return $sum % 10 === 0;
    }
}
