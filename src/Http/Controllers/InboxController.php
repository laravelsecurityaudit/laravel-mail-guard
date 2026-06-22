<?php

namespace LaravelSecurityAudit\MailGuard\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use LaravelSecurityAudit\MailGuard\Models\Finding;
use LaravelSecurityAudit\MailGuard\Models\Message;

class InboxController
{
    public function index(Request $request): View
    {
        $risk = $request->query('risk');
        $risk = is_string($risk) && $risk !== '' ? $risk : null;

        $messages = Message::query()
            ->select(['id', 'mailer', 'subject', 'recipients', 'sender', 'risk_level', 'findings_count', 'blocked', 'captured_at'])
            ->when($risk !== null, fn ($query) => $query->where('risk_level', $risk))
            ->orderByRaw("CASE risk_level WHEN 'critical' THEN 3 WHEN 'warning' THEN 2 WHEN 'info' THEN 1 ELSE 0 END DESC")
            ->latest('captured_at')
            ->latest('id')
            ->paginate((int) config('mail-guard.per_page', 20))
            ->withQueryString();

        return view('mail-guard::inbox.index', [
            'messages' => $messages,
            'risk' => $risk,
        ]);
    }

    public function show(Message $message): JsonResponse
    {
        $message->load('findings');

        return response()->json([
            'id' => $message->id,
            'mailer' => $message->mailer,
            'subject' => $message->subject,
            'source' => $message->source,
            'sender' => $message->sender,
            'recipients' => $message->recipients,
            'cc' => $message->cc,
            'bcc' => $message->bcc,
            'reply_to' => $message->reply_to,
            'html_body' => $message->html_body,
            'text_body' => $message->text_body,
            'headers' => $message->headers,
            'attachments' => $message->attachments ?? [],
            'risk_level' => $message->risk_level,
            'blocked' => (bool) $message->blocked,
            'captured_at' => $message->captured_at?->toDayDateTimeString(),
            'findings' => $message->findings
                ->map(static fn (Finding $finding): array => [
                    'rule_id' => $finding->rule_id,
                    'severity' => $finding->severity,
                    'confidence' => $finding->confidence,
                    'title' => $finding->title,
                    'detail' => $finding->detail,
                    'location' => $finding->location,
                    'snippet' => $finding->snippet,
                ])
                ->all(),
            'delete_url' => route('mail-guard.destroy', $message),
        ]);
    }

    public function destroy(Request $request, Message $message): RedirectResponse|Response
    {
        $message->delete();

        if ($request->wantsJson()) {
            return response()->noContent();
        }

        return redirect()
            ->route('mail-guard.index')
            ->with('mail-guard.status', 'Message deleted.');
    }

    public function destroyAll(Request $request): RedirectResponse|Response
    {
        Message::query()->delete();

        if ($request->wantsJson()) {
            return response()->noContent();
        }

        return redirect()
            ->route('mail-guard.index')
            ->with('mail-guard.status', 'All captured messages deleted.');
    }
}
