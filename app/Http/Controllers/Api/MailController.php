<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMailRequest;
use App\Jobs\SendMailJob;
use App\Models\MailLog;
use Illuminate\Http\JsonResponse;

class MailController extends Controller
{
    public function send(SendMailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $mailLog = MailLog::create([
            'to'        => $validated['to'],
            'cc'        => $validated['cc'] ?? null,
            'bcc'       => $validated['bcc'] ?? null,
            'subject'   => $validated['subject'],
            'text_body' => $validated['text_body'] ?? null,
            'html_body' => $validated['html_body'] ?? null,
        ]);

        SendMailJob::dispatch($mailLog->id, $validated['attachments'] ?? []);

        return response()->json([
            'message'     => 'Mail queued for delivery.',
            'mail_log_id' => $mailLog->id,
        ], 202);
    }
}
