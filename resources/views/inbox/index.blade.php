<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mail Guard</title>
        <style>
            :root {
                color-scheme: dark;
                --bg: #0f1115; --panel: #171a1f; --panel-2: #1f242b; --border: #2c333c;
                --text: #f3f5f7; --muted: #9aa3ad; --dim: #6b7480;
                --ok: #5bd6a3; --info: #6aa7ff; --warning: #f3c969; --critical: #f9736b;
            }
            * { box-sizing: border-box; }
            body { margin: 0; background: var(--bg); color: var(--text); line-height: 1.5;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
            a { color: inherit; }
            .wrap { width: min(1180px, calc(100% - 32px)); margin: 0 auto; padding: 44px 0; }
            .head { display: flex; justify-content: space-between; gap: 20px; align-items: end;
                border-bottom: 1px solid var(--border); padding-bottom: 24px; }
            .eyebrow { margin: 0 0 8px; color: var(--dim); font-size: 12px; font-weight: 700;
                letter-spacing: .18em; text-transform: uppercase; }
            h1 { margin: 0; font-size: clamp(28px, 4vw, 40px); }
            .sub { margin: 10px 0 0; color: var(--muted); font-size: 14px; max-width: 640px; }
            .btn { appearance: none; border: 1px solid var(--border); border-radius: 8px; background: transparent;
                color: var(--text); cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
                min-height: 38px; padding: 7px 13px; font: inherit; font-size: 14px; font-weight: 600;
                text-decoration: none; }
            .btn:hover { border-color: var(--muted); }
            .btn-danger { color: var(--critical); border-color: rgb(249 115 107 / 45%); }
            .actions { display: flex; gap: 8px; align-items: center; }
            .actions form { margin: 0; }
            .filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 24px 0 0; }
            .chip { border: 1px solid var(--border); border-radius: 999px; padding: 6px 14px; font-size: 13px;
                font-weight: 600; color: var(--muted); text-decoration: none; }
            .chip.is-active { background: var(--panel-2); color: var(--text); border-color: var(--muted); }
            .flash { margin-top: 18px; border: 1px solid rgb(91 214 163 / 35%); border-radius: 8px;
                background: rgb(91 214 163 / 10%); color: var(--ok); padding: 11px 14px; font-size: 14px; }
            .table-wrap { margin-top: 20px; border: 1px solid var(--border); border-radius: 10px;
                overflow: hidden; background: var(--panel); }
            .scroll { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; min-width: 880px; }
            th, td { padding: 14px 16px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--border);
                font-size: 14px; }
            tr:last-child td { border-bottom: 0; }
            th { background: #0c0e11; color: var(--dim); font-size: 11px; font-weight: 800;
                letter-spacing: .14em; text-transform: uppercase; }
            tbody tr:hover { background: var(--panel-2); }
            .nowrap { white-space: nowrap; }
            .muted { color: var(--muted); } .dim { color: var(--dim); }
            .cell-limited { max-width: 320px; overflow-wrap: anywhere; }
            .small { display: block; margin-top: 4px; color: var(--dim); font-size: 12px; }
            .badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 9px; border-radius: 999px;
                font-size: 12px; font-weight: 700; text-transform: capitalize; border: 1px solid transparent; }
            .badge::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
            .badge-ok { color: var(--ok); border-color: rgb(91 214 163 / 35%); }
            .badge-info { color: var(--info); border-color: rgb(106 167 255 / 35%); }
            .badge-warning { color: var(--warning); border-color: rgb(243 201 105 / 35%); }
            .badge-critical { color: var(--critical); border-color: rgb(249 115 107 / 45%); }
            .tag-blocked { margin-left: 8px; color: var(--critical); font-size: 11px; font-weight: 800;
                text-transform: uppercase; letter-spacing: .08em; }
            .empty { padding: 46px 16px; text-align: center; color: var(--dim); }
            .pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px;
                margin-top: 20px; color: var(--muted); font-size: 14px; }
            .pagination-actions { display: flex; gap: 8px; }
            .disabled { opacity: .45; cursor: default; }
            .modal { position: fixed; inset: 0; z-index: 50; display: none; padding: 24px; overflow-y: auto; }
            .modal.is-open { display: block; }
            .backdrop { position: fixed; inset: 0; background: rgb(0 0 0 / 76%); }
            .panel { position: relative; width: min(1100px, 100%); min-height: 80vh; margin: 0 auto; display: flex;
                flex-direction: column; overflow: hidden; border: 1px solid var(--border); border-radius: 12px;
                background: var(--bg); box-shadow: 0 30px 90px rgb(0 0 0 / 45%); }
            .modal-head { display: flex; justify-content: space-between; gap: 20px; padding: 20px;
                border-bottom: 1px solid var(--border); }
            .modal-title { margin: 8px 0 0; font-size: 21px; overflow-wrap: anywhere; }
            .meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin: 14px 0 0; }
            .meta dt { color: var(--dim); font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
            .meta dd { margin: 4px 0 0; color: var(--muted); overflow-wrap: anywhere; }
            .close { width: 40px; height: 40px; flex: 0 0 auto; border-radius: 50%; font-size: 24px; padding: 0; }
            .tabs { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 20px; border-bottom: 1px solid var(--border); }
            .tab.is-active { background: var(--panel-2); border-color: var(--muted); }
            .tab .count { font-size: 11px; color: var(--critical); font-weight: 800; }
            .body { flex: 1; min-height: 420px; background: var(--panel); }
            .loading, .error { padding: 34px 20px; color: var(--muted); text-align: center; }
            .error { color: var(--critical); }
            .pane { display: none; min-height: 460px; }
            .pane.is-active { display: block; }
            .frame { width: 100%; min-height: 460px; border: 0; background: #fff; }
            pre.pane { margin: 0; padding: 20px; white-space: pre-wrap; overflow-wrap: anywhere; color: #e7eaee;
                font: 12px/1.6 ui-monospace, SFMono-Regular, Menlo, monospace; }
            .findings { padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
            .finding { border: 1px solid var(--border); border-radius: 10px; padding: 14px; background: var(--bg); }
            .finding-top { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
            .finding-title { font-weight: 700; }
            .finding-detail { margin: 8px 0 0; color: var(--muted); font-size: 14px; }
            .finding-snippet { margin: 10px 0 0; padding: 8px 10px; border-radius: 8px; background: var(--panel-2);
                color: var(--text); font: 12px/1.5 ui-monospace, Menlo, monospace; overflow-wrap: anywhere; }
            .finding-where { color: var(--dim); font-size: 12px; }
            @media (max-width: 720px) {
                .head, .modal-head { flex-direction: column; align-items: stretch; }
                .meta { grid-template-columns: 1fr; }
                .pagination { flex-direction: column; align-items: stretch; }
            }
        </style>
    </head>
    <body>
        @php
            $badge = fn (?string $level) => 'badge badge-'.(in_array($level, ['critical', 'warning', 'info'], true) ? $level : 'ok');
        @endphp
        <main class="wrap" data-guard>
            <header class="head">
                <div>
                    <p class="eyebrow">Mail Guard</p>
                    <h1>Outgoing mail</h1>
                    <p class="sub">Captured outgoing mail with security and compliance findings for this environment.</p>
                </div>
                <div class="actions">
                    <a class="btn" href="{{ url()->current() }}">Refresh</a>
                    @if ($messages->total() > 0)
                        <form method="POST" action="{{ route('mail-guard.destroy-all') }}" data-guard-clear>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Clear all</button>
                        </form>
                    @endif
                </div>
            </header>

            @if (session('mail-guard.status'))
                <div class="flash" role="status">{{ session('mail-guard.status') }}</div>
            @endif

            <nav class="filters" aria-label="Filter by risk">
                @php $levels = ['' => 'All', 'critical' => 'Critical', 'warning' => 'Warning', 'info' => 'Info', 'ok' => 'Clean']; @endphp
                @foreach ($levels as $value => $label)
                    <a class="chip {{ (string) $risk === (string) $value ? 'is-active' : '' }}"
                       href="{{ url()->current() }}{{ $value === '' ? '' : '?risk='.$value }}">{{ $label }}</a>
                @endforeach
            </nav>

            <section class="table-wrap" aria-label="Captured mail">
                <div class="scroll">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Risk</th>
                                <th scope="col">Captured</th>
                                <th scope="col">To</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Findings</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr>
                                    <td class="nowrap"><span class="{{ $badge($message->risk_level) }}">{{ $message->risk_level ?: 'ok' }}</span></td>
                                    <td class="nowrap muted">{{ $message->captured_at?->format('M j, Y H:i') }}</td>
                                    <td class="cell-limited">
                                        {{ $message->recipients ?: 'No recipient' }}
                                        @if ($message->sender)<span class="small">From {{ $message->sender }}</span>@endif
                                    </td>
                                    <td class="cell-limited">
                                        {{ $message->subject ?: '(No subject)' }}
                                        @if ($message->blocked)<span class="tag-blocked">Blocked</span>@endif
                                    </td>
                                    <td class="nowrap muted">{{ $message->findings_count }}</td>
                                    <td class="nowrap">
                                        <div class="actions">
                                            <button type="button" class="btn" data-guard-open
                                                data-url="{{ route('mail-guard.show', $message) }}">View</button>
                                            <form method="POST" action="{{ route('mail-guard.destroy', $message) }}" data-guard-delete-row>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">No mail has been captured yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($messages->hasPages())
                <nav class="pagination" aria-label="Pagination">
                    <div>Page {{ $messages->currentPage() }} of {{ $messages->lastPage() }}</div>
                    <div class="pagination-actions">
                        @if ($messages->onFirstPage())
                            <span class="btn disabled">Previous</span>
                        @else
                            <a class="btn" href="{{ $messages->previousPageUrl() }}">Previous</a>
                        @endif
                        @if ($messages->hasMorePages())
                            <a class="btn" href="{{ $messages->nextPageUrl() }}">Next</a>
                        @else
                            <span class="btn disabled">Next</span>
                        @endif
                    </div>
                </nav>
            @endif

            <section class="modal" data-guard-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="guard-modal-title">
                <div class="backdrop" data-guard-close></div>
                <div class="panel">
                    <header class="modal-head">
                        <div>
                            <p class="eyebrow" data-guard-risk>Loading</p>
                            <h2 class="modal-title" id="guard-modal-title" data-guard-subject>(No subject)</h2>
                            <dl class="meta">
                                <div><dt>From</dt><dd data-guard-sender>None</dd></div>
                                <div><dt>To</dt><dd data-guard-recipients>None</dd></div>
                            </dl>
                        </div>
                        <div class="actions">
                            <form method="POST" data-guard-delete-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" data-guard-delete-button disabled>Delete</button>
                            </form>
                            <button type="button" class="btn close" data-guard-close aria-label="Close">&times;</button>
                        </div>
                    </header>
                    <div class="tabs" role="tablist">
                        <button type="button" class="btn tab is-active" data-guard-tab="security">Security <span class="count" data-guard-finding-count></span></button>
                        <button type="button" class="btn tab" data-guard-tab="html">HTML</button>
                        <button type="button" class="btn tab" data-guard-tab="text">Text</button>
                        <button type="button" class="btn tab" data-guard-tab="headers">Headers</button>
                    </div>
                    <div class="body">
                        <div class="loading" data-guard-loading>Loading message...</div>
                        <div class="error" data-guard-error hidden></div>
                        <div class="pane findings is-active" data-guard-pane="security" data-guard-findings hidden></div>
                        <div class="pane" data-guard-pane="html" hidden>
                            <iframe class="frame" title="HTML preview" sandbox data-guard-frame></iframe>
                        </div>
                        <pre class="pane" data-guard-pane="text" data-guard-text hidden></pre>
                        <pre class="pane" data-guard-pane="headers" data-guard-headers hidden></pre>
                    </div>
                </div>
            </section>
        </main>

        <script>
            (() => {
                const root = document.querySelector('[data-guard]');
                const modal = root.querySelector('[data-guard-modal]');
                const frame = root.querySelector('[data-guard-frame]');
                const loading = root.querySelector('[data-guard-loading]');
                const error = root.querySelector('[data-guard-error]');
                const deleteForm = root.querySelector('[data-guard-delete-form]');
                const deleteButton = root.querySelector('[data-guard-delete-button]');
                const panes = Array.from(root.querySelectorAll('[data-guard-pane]'));
                const tabs = Array.from(root.querySelectorAll('[data-guard-tab]'));
                let message = null;

                const badgeClass = (level) => 'badge badge-' + (['critical', 'warning', 'info'].includes(level) ? level : 'ok');

                function setText(selector, value, fallback = '') {
                    root.querySelector(selector).textContent = value || fallback;
                }

                function escapeHtml(value) {
                    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                }

                function showTab(tab) {
                    tabs.forEach((b) => b.classList.toggle('is-active', b.dataset.guardTab === tab));
                    panes.forEach((p) => {
                        const active = p.dataset.guardPane === tab;
                        p.hidden = !active;
                        p.classList.toggle('is-active', active);
                    });
                    if (tab === 'html' && message) {
                        frame.srcdoc = message.html_body || '<pre style="padding:24px;font:14px ui-monospace,monospace">No HTML body.</pre>';
                    }
                }

                function renderFindings(findings) {
                    const wrap = root.querySelector('[data-guard-findings]');
                    wrap.replaceChildren();
                    const count = root.querySelector('[data-guard-finding-count]');
                    count.textContent = findings && findings.length ? String(findings.length) : '';

                    if (!findings || findings.length === 0) {
                        const p = document.createElement('p');
                        p.className = 'muted';
                        p.textContent = 'No findings. This message looks clean.';
                        wrap.append(p);
                        return;
                    }

                    findings.forEach((f) => {
                        const card = document.createElement('article');
                        card.className = 'finding';
                        const where = f.location ? ' in ' + escapeHtml(f.location) : '';
                        const snippet = f.snippet ? '<div class="finding-snippet"></div>' : '';
                        card.innerHTML = '<div class="finding-top"><span class="' + badgeClass(f.severity) + '"></span>'
                            + '<span class="finding-title"></span><span class="finding-where">' + escapeHtml(f.rule_id) + where + '</span></div>'
                            + '<p class="finding-detail"></p>' + snippet;
                        card.querySelector('.badge').textContent = f.severity;
                        card.querySelector('.finding-title').textContent = f.title;
                        card.querySelector('.finding-detail').textContent = f.detail || '';
                        if (f.snippet) card.querySelector('.finding-snippet').textContent = f.snippet;
                        wrap.append(card);
                    });
                }

                function render() {
                    const risk = message.risk_level || 'ok';
                    const riskEl = root.querySelector('[data-guard-risk]');
                    riskEl.textContent = message.blocked ? ('Blocked - ' + risk) : risk;
                    setText('[data-guard-subject]', message.subject, '(No subject)');
                    setText('[data-guard-sender]', message.sender, 'None');
                    setText('[data-guard-recipients]', message.recipients, 'None');
                    root.querySelector('[data-guard-text]').textContent = message.text_body || 'No text body.';
                    root.querySelector('[data-guard-headers]').textContent = message.headers || 'No headers.';
                    deleteForm.action = message.delete_url;
                    deleteButton.disabled = !message.delete_url;
                    renderFindings(message.findings);
                    loading.hidden = true;
                    error.hidden = true;
                    showTab('security');
                }

                function openModal() {
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal() {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    message = null;
                    frame.srcdoc = '';
                    deleteForm.removeAttribute('action');
                    deleteButton.disabled = true;
                }

                async function load(url) {
                    openModal();
                    loading.hidden = false;
                    error.hidden = true;
                    panes.forEach((p) => { p.hidden = true; });
                    try {
                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error('Message could not be loaded.');
                        message = await response.json();
                        render();
                    } catch (e) {
                        loading.hidden = true;
                        error.hidden = false;
                        error.textContent = e.message || 'Message could not be loaded.';
                    }
                }

                root.querySelectorAll('[data-guard-open]').forEach((b) => b.addEventListener('click', () => load(b.dataset.url)));
                root.querySelectorAll('[data-guard-close]').forEach((b) => b.addEventListener('click', closeModal));
                tabs.forEach((b) => b.addEventListener('click', () => showTab(b.dataset.guardTab)));

                root.querySelectorAll('[data-guard-delete-row], [data-guard-delete-form]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (!window.confirm('Delete this captured message?')) event.preventDefault();
                    });
                });
                root.querySelectorAll('[data-guard-clear]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (!window.confirm('Delete ALL captured messages? This cannot be undone.')) event.preventDefault();
                    });
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
                });
            })();
        </script>
    </body>
</html>
