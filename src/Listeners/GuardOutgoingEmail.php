<?php

namespace LaravelSecurityAudit\MailGuard\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;
use LaravelSecurityAudit\MailGuard\Events\FindingsDetected;
use LaravelSecurityAudit\MailGuard\Events\MailBlocked;
use LaravelSecurityAudit\MailGuard\Guard\GuardDecision;
use LaravelSecurityAudit\MailGuard\Models\Finding as FindingModel;
use LaravelSecurityAudit\MailGuard\Models\Message;
use LaravelSecurityAudit\MailGuard\Redaction\Redactor;
use LaravelSecurityAudit\MailGuard\Scanning\Confidence;
use LaravelSecurityAudit\MailGuard\Scanning\Finding;
use LaravelSecurityAudit\MailGuard\Scanning\MessageContext;
use LaravelSecurityAudit\MailGuard\Scanning\Scanner;
use LaravelSecurityAudit\MailGuard\Scanning\Severity;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

class GuardOutgoingEmail
{
    public function __construct(
        private readonly Scanner $scanner,
        private readonly Redactor $redactor,
    ) {}

    /**
     * Returning false halts the send. MessageSending is dispatched with a
     * halting dispatcher, so a non-null false return prevents delivery.
     */
    public function handle(MessageSending $event): ?bool
    {
        $captureEnabled = (bool) config('mail-guard.enabled');
        $guardEnabled = (bool) config('mail-guard.guard.enabled');

        if (! $captureEnabled && ! $guardEnabled) {
            return null;
        }

        try {
            $context = new MessageContext(
                $event->message,
                $event->data,
                (int) config('mail-guard.scan.max_bytes', 512_000),
            );

            $findings = $this->scanner->scan($context);

            $decision = $this->decision();
            $block = $guardEnabled
                && $this->guardAppliesToEnvironment()
                && ! $this->isBypassed($context)
                && $decision->shouldBlock($findings);

            $mailer = is_string($event->data['mailer'] ?? null)
                ? $event->data['mailer']
                : (string) config('mail.default');

            $message = null;
            if ($captureEnabled || $block) {
                $message = $this->store($event->message, $context, $findings, $mailer, $block);
            }

            if ($block) {
                $blocking = $decision->blocking($findings);
                event(new MailBlocked($context, $blocking));
                logger()->warning('mail-guard blocked an outgoing email', [
                    'rules' => array_map(static fn (Finding $f): string => $f->ruleId, $blocking),
                ]);

                return false;
            }

            if ($message !== null && $findings !== []) {
                event(new FindingsDetected($message, $findings));
            }
        } catch (Throwable $exception) {
            report($exception);

            if (! (bool) config('mail-guard.guard.fail_open', true)) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param  list<Finding>  $findings
     */
    private function store(Email $email, MessageContext $context, array $findings, string $mailer, bool $blocked): Message
    {
        $message = Message::query()->create([
            'mailer' => $mailer,
            'subject' => $this->redactor->redact($email->getSubject(), $findings),
            'source' => $context->source(),
            'sender' => $this->formatAddresses($email->getFrom()),
            'recipients' => $this->formatAddresses($email->getTo()),
            'cc' => $this->formatAddresses($email->getCc()),
            'bcc' => $this->formatAddresses($email->getBcc()),
            'reply_to' => $this->formatAddresses($email->getReplyTo()),
            'to_addresses' => $this->mapAddresses($email->getTo()),
            'html_body' => $this->redactor->redact($context->html(), $findings),
            'text_body' => $this->redactor->redact($context->text(), $findings),
            'headers' => $email->getHeaders()->toString(),
            'attachments' => $this->mapAttachments($email),
            'risk_level' => $this->scanner->riskLevel($findings),
            'findings_count' => count($findings),
            'blocked' => $blocked,
            'captured_at' => now(),
        ]);

        foreach ($findings as $finding) {
            FindingModel::query()->create(array_merge(
                ['message_id' => $message->id, 'created_at' => now()],
                $finding->toStorableArray(),
            ));
        }

        return $message;
    }

    private function decision(): GuardDecision
    {
        return new GuardDecision(
            Severity::tryFrom((string) config('mail-guard.guard.min_severity', 'critical')) ?? Severity::Critical,
            Confidence::tryFrom((string) config('mail-guard.guard.min_confidence', 'high')) ?? Confidence::High,
        );
    }

    private function guardAppliesToEnvironment(): bool
    {
        $environments = config('mail-guard.guard.environments');

        if ($environments === null) {
            return true;
        }

        return app()->environment((array) $environments);
    }

    private function isBypassed(MessageContext $context): bool
    {
        if ($context->hasHeader('X-Mail-Guard-Bypass')) {
            return true;
        }

        $source = $context->source();
        $allow = (array) config('mail-guard.guard.allow_source', []);

        return $source !== null && in_array($source, $allow, true);
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function formatAddresses(array $addresses): ?string
    {
        if ($addresses === []) {
            return null;
        }

        return (new Collection($addresses))
            ->map(static fn (Address $address): string => $address->getName() === ''
                ? $address->getAddress()
                : $address->getName().' <'.$address->getAddress().'>')
            ->implode(', ');
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return list<array{address: string, name: string|null}>
     */
    private function mapAddresses(array $addresses): array
    {
        return array_map(static fn (Address $address): array => [
            'address' => $address->getAddress(),
            'name' => $address->getName() !== '' ? $address->getName() : null,
        ], array_values($addresses));
    }

    /**
     * @return list<array{filename: string|null, content_type: string|null, disposition: string|null}>
     */
    private function mapAttachments(Email $email): array
    {
        return array_map(static fn (DataPart $part): array => [
            'filename' => $part->getFilename(),
            'content_type' => $part->getContentType(),
            'disposition' => $part->getDisposition(),
        ], array_values($email->getAttachments()));
    }
}
