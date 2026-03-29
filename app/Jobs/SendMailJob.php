<?php

namespace App\Jobs;

use App\Enums\MailStatus;
use App\Models\MailLog;
use App\Services\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendMailJob implements ShouldQueue
{
    use Queueable;

    public int   $tries   = 3;
    public array $backoff  = [10, 60];
    public int   $timeout = 30;

    public function __construct(
        private readonly int   $mailLogId,
        private readonly array $attachments = [],
    ) {}

    public function handle(MailService $mailService): void
    {
        $log = MailLog::findOrFail($this->mailLogId);

        $mailService->send(
            to:          $log->to,
            cc:          $log->cc,
            bcc:         $log->bcc,
            subject:     $log->subject,
            textBody:    $log->text_body,
            htmlBody:    $log->html_body,
            attachments: $this->attachments,
        );

        $log->update([
            'status'  => MailStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        MailLog::where('id', $this->mailLogId)->update([
            'status'        => MailStatus::Failed,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
