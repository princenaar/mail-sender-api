<?php

namespace App\Http\Controllers;

use App\Support\MailLogsAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MailLogAccessController extends Controller
{
    public function showLogin(Request $request): Response|View
    {
        abort_unless(MailLogsAuthentication::isConfigured(), 503, 'The mail logs password is not configured.');

        return view('logs.login', [
            'alreadyAuthenticated' => MailLogsAuthentication::isAuthenticated($request),
        ]);
    }

    public function login(Request $request): Response
    {
        $expectedPassword = config('services.mail_logs_password');

        abort_unless(MailLogsAuthentication::isConfigured(), 503, 'The mail logs password is not configured.');

        $password = $request->input('password');

        if (! is_string($password) || ! hash_equals($expectedPassword, $password)) {
            return response()->view('logs.login', [
                'alreadyAuthenticated' => false,
                'error' => 'The password is incorrect.',
            ], 422);
        }

        $request->session()->regenerate();
        $request->session()->put(MailLogsAuthentication::SESSION_KEY, MailLogsAuthentication::fingerprint());

        return redirect()->intended(route('logs.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(MailLogsAuthentication::SESSION_KEY);
        $request->session()->regenerateToken();

        return redirect()->route('logs.login');
    }
}
