<?php

namespace LaravelSecurityAudit\MailGuard\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use LaravelSecurityAudit\MailGuard\Models\Finding;
use LaravelSecurityAudit\MailGuard\Reporting\SarifReport;

class ScanCommand extends Command
{
    protected $signature = 'mail-guard:scan
        {--min-severity=critical : Exit non-zero when a finding at or above this severity exists (info, warning, critical)}
        {--format=table : Output format: table, json, or sarif}
        {--output= : Write the report to this file instead of stdout}
        {--since= : Only consider messages captured at or after this date/time}';

    protected $description = 'Report captured mail findings and fail when any meet the severity threshold.';

    private const RANKS = ['info' => 1, 'warning' => 2, 'critical' => 3];

    public function handle(): int
    {
        $minSeverity = strtolower((string) $this->option('min-severity'));
        $minRank = self::RANKS[$minSeverity] ?? 3;

        $findings = $this->findings();

        $format = strtolower((string) $this->option('format'));
        $payload = match ($format) {
            'json' => $this->toJson($findings),
            'sarif' => (new SarifReport)->build($findings),
            default => null,
        };

        if ($payload !== null) {
            $this->emit($payload);
        } else {
            $this->renderTable($findings);
        }

        $offending = $findings->filter(
            fn (Finding $finding): bool => (self::RANKS[strtolower((string) $finding->severity)] ?? 0) >= $minRank,
        );

        if ($offending->isNotEmpty()) {
            $this->components->error($offending->count().' finding(s) at or above ['.$minSeverity.'].');

            return self::FAILURE;
        }

        $this->components->info('No findings at or above ['.$minSeverity.'].');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Finding>
     */
    private function findings(): Collection
    {
        $query = Finding::query()->with('message');

        $since = $this->option('since');
        if (is_string($since) && $since !== '') {
            $query->whereHas('message', fn ($q) => $q->where('captured_at', '>=', $since));
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Finding>  $findings
     */
    private function toJson(Collection $findings): string
    {
        return (string) json_encode(
            $findings->map(static fn (Finding $finding): array => [
                'message_id' => $finding->message_id,
                'rule_id' => $finding->rule_id,
                'severity' => $finding->severity,
                'confidence' => $finding->confidence,
                'title' => $finding->title,
                'location' => $finding->location,
            ])->all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * @param  Collection<int, Finding>  $findings
     */
    private function renderTable(Collection $findings): void
    {
        if ($findings->isEmpty()) {
            $this->components->info('No findings captured.');

            return;
        }

        $this->table(
            ['Severity', 'Rule', 'Subject', 'Captured'],
            $findings->map(static fn (Finding $finding): array => [
                $finding->severity,
                $finding->rule_id,
                (string) ($finding->message?->subject ?? '(unknown)'),
                (string) ($finding->message?->captured_at ?? ''),
            ])->all(),
        );
    }

    private function emit(string $payload): void
    {
        $output = $this->option('output');

        if (is_string($output) && $output !== '') {
            file_put_contents($output, $payload);
            $this->components->info('Report written to '.$output);

            return;
        }

        $this->line($payload);
    }
}
