<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MailLogAccessController extends Controller
{
    public function showLogin(Request $request): Response|View
    {
        abort_if(blank(config('services.mail_logs_password')), 503, 'The mail logs password is not configured.');

        return view('logs.login', [
            'alreadyAuthenticated' => $request->session()->get('mail_logs_authenticated', false),
        ]);
    }

    public function login(Request $request): Response
    {
        $expectedPassword = config('services.mail_logs_password');

        abort_if(blank($expectedPassword), 503, 'The mail logs password is not configured.');

        $password = $request->input('password');

        if (! is_string($password) || ! hash_equals($expectedPassword, $password)) {
            return response()->view('logs.login', [
                'alreadyAuthenticated' => false,
                'error' => 'The password is incorrect.',
            ], 422);
        }

        $request->session()->regenerate();
        $request->session()->put('mail_logs_authenticated', true);

        return redirect()->intended(route('logs.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('mail_logs_authenticated');
        $request->session()->regenerateToken();

        return redirect()->route('logs.login');
    }
}
