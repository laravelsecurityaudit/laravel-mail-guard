<?php

namespace LaravelSecurityAudit\MailGuard\Reporting;

use Illuminate\Support\Collection;
use LaravelSecurityAudit\MailGuard\Models\Finding;

class SarifReport
{
    /**
     * Build a SARIF 2.1.0 document from findings.
     *
     * Runtime data leaks do not map to a source line, so each result points at
     * the originating Mailable or Notification class when known, otherwise at a
     * synthetic mail-guard URI.
     *
     * @param  Collection<int, Finding>  $findings
     */
    public function build(Collection $findings): string
    {
        $levels = ['info' => 'note', 'warning' => 'warning', 'critical' => 'error'];

        $ruleIds = [];
        $results = [];

        foreach ($findings as $finding) {
            $ruleIds[$finding->rule_id] = true;
            $uri = $finding->message?->source ?: ('mail-guard://message/'.$finding->message_id);

            $results[] = [
                'ruleId' => $finding->rule_id,
                'level' => $levels[strtolower((string) $finding->severity)] ?? 'warning',
                'message' => ['text' => trim($finding->title.' '.(string) $finding->detail)],
                'locations' => [[
                    'physicalLocation' => [
                        'artifactLocation' => ['uri' => $uri],
                    ],
                ]],
            ];
        }

        $rules = array_map(static fn (string $id): array => ['id' => $id], array_keys($ruleIds));

        return (string) json_encode([
            '$schema' => 'https://json.schemastore.org/sarif-2.1.0.json',
            'version' => '2.1.0',
            'runs' => [[
                'tool' => [
                    'driver' => [
                        'name' => 'laravel-mail-guard',
                        'informationUri' => 'https://github.com/laravelsecurityaudit/laravel-mail-guard',
                        'rules' => array_values($rules),
                    ],
                ],
                'results' => $results,
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
