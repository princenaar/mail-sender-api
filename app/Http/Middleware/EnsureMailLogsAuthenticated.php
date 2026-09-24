<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMailLogsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (blank(config('services.mail_logs_password'))) {
            abort(503, 'The mail logs password is not configured.');
        }

        if (! $request->session()->get('mail_logs_authenticated', false)) {
            return redirect()->route('logs.login');
        }

        return $next($request);
    }
}
