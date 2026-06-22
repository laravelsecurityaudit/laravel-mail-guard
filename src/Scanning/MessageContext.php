<?php

namespace LaravelSecurityAudit\MailGuard\Scanning;

use Symfony\Component\Mime\Email;

class MessageContext
{
    private ?string $bodyCache = null;

    /**
     * @param  array<string, mixed>  $data  the MessageSending event data
     */
    public function __construct(
        private readonly Email $email,
        private readonly array $data = [],
        private readonly int $maxBytes = 512_000,
    ) {}

    public function subject(): ?string
    {
        return $this->email->getSubject();
    }

    public function html(): ?string
    {
        return $this->normalizeBody($this->email->getHtmlBody());
    }

    public function text(): ?string
    {
        return $this->normalizeBody($this->email->getTextBody());
    }

    public function rawHeaders(): string
    {
        return $this->email->getHeaders()->toString();
    }

    public function hasHeader(string $name): bool
    {
        return $this->email->getHeaders()->has($name);
    }

    /**
     * Best effort: Laravel exposes the originating Notification or Mailable in
     * the event data for some send paths. Returns the class name when known.
     */
    public function source(): ?string
    {
        foreach (['__laravel_notification__', 'mailable'] as $key) {
            $value = $this->data[$key] ?? null;

            if (is_object($value)) {
                return $value::class;
            }

            if (is_string($value) && class_exists($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Combined subject, text, and HTML, capped at the configured byte limit.
     */
    public function body(): string
    {
        if ($this->bodyCache !== null) {
            return $this->bodyCache;
        }

        $parts = array_filter(
            [$this->subject(), $this->text(), $this->html()],
            static fn ($part): bool => is_string($part) && $part !== '',
        );

        $combined = implode("\n", $parts);

        if (strlen($combined) > $this->maxBytes) {
            $combined = substr($combined, 0, $this->maxBytes);
        }

        return $this->bodyCache = $combined;
    }

    public function wasTruncated(): bool
    {
        $full = implode("\n", array_filter(
            [$this->subject(), $this->text(), $this->html()],
            static fn ($part): bool => is_string($part) && $part !== '',
        ));

        return strlen($full) > $this->maxBytes;
    }

    /**
     * Raw <img ...> tags found in the HTML body.
     *
     * @return list<string>
     */
    public function imageTags(): array
    {
        $html = $this->html();

        if (! is_string($html) || $html === '') {
            return [];
        }

        preg_match_all('/<img\b[^>]*>/i', $html, $matches);

        return $matches[0];
    }

    /**
     * @param  string|resource|null  $body
     */
    private function normalizeBody($body): ?string
    {
        if (is_string($body)) {
            return $body;
        }

        if (is_resource($body)) {
            $contents = stream_get_contents($body);

            return $contents === false ? null : $contents;
        }

        return null;
    }
}
