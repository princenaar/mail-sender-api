<?php

namespace App\Http\Middleware;

use App\Support\MailLogsAuthentication;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMailLogsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! MailLogsAuthentication::isConfigured()) {
            abort(503, 'The mail logs password is not configured.');
        }

        if (! MailLogsAuthentication::isAuthenticated($request)) {
            $request->session()->forget(MailLogsAuthentication::SESSION_KEY);

            return redirect()->route('logs.login');
        }

        return $next($request);
    }
}
