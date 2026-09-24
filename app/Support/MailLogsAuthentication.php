<?php

namespace App\Support;

use Illuminate\Http\Request;

class MailLogsAuthentication
{
    public const SESSION_KEY = 'mail_logs_password_fingerprint';

    public static function isConfigured(): bool
    {
        return filled(config('services.mail_logs_password'));
    }

    public static function fingerprint(): string
    {
        return hash_hmac(
            'sha256',
            (string) config('services.mail_logs_password'),
            (string) config('app.key'),
        );
    }

    public static function isAuthenticated(Request $request): bool
    {
        $storedFingerprint = $request->session()->get(self::SESSION_KEY);

        return self::isConfigured()
            && is_string($storedFingerprint)
            && hash_equals(self::fingerprint(), $storedFingerprint);
    }
}
