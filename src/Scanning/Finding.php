<?php

namespace LaravelSecurityAudit\MailGuard\Scanning;

/**
 * Immutable result emitted by a rule.
 *
 * The raw matched value lives in $meta['match'] and is used only for redaction.
 * It must be stripped before a finding is persisted.
 */
final class Finding
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $ruleId,
        public readonly Severity $severity,
        public readonly Confidence $confidence,
        public readonly string $title,
        public readonly string $detail,
        public readonly string $location = 'body',
        public readonly ?string $snippet = null,
        public readonly array $meta = [],
    ) {}

    public function withSeverity(Severity $severity): self
    {
        return new self(
            $this->ruleId,
            $severity,
            $this->confidence,
            $this->title,
            $this->detail,
            $this->location,
            $this->snippet,
            $this->meta,
        );
    }

    public function rawMatch(): ?string
    {
        $match = $this->meta['match'] ?? null;

        return is_string($match) ? $match : null;
    }

    /**
     * Array shape for persistence, without the raw match value.
     *
     * @return array<string, mixed>
     */
    public function toStorableArray(): array
    {
        $meta = $this->meta;
        unset($meta['match']);

        return [
            'rule_id' => $this->ruleId,
            'severity' => $this->severity->value,
            'confidence' => $this->confidence->value,
            'title' => $this->title,
            'detail' => $this->detail,
            'location' => $this->location,
            'snippet' => $this->snippet,
            'meta' => $meta === [] ? null : $meta,
        ];
    }
}
