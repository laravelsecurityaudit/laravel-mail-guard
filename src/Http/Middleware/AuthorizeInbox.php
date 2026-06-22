<?php

namespace LaravelSecurityAudit\MailGuard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeInbox
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $gate = config('mail-guard.gate');

        if (is_string($gate) && $gate !== '') {
            if (Gate::denies($gate)) {
                abort(403);
            }

            return $next($request);
        }

        $unguarded = (array) config('mail-guard.unguarded_environments', ['local']);

        if (! app()->environment($unguarded)) {
            abort(403, 'Mail Guard inbox has no access gate configured. Set MAIL_GUARD_GATE or add this environment to mail-guard.unguarded_environments.');
        }

        return $next($request);
    }
}
