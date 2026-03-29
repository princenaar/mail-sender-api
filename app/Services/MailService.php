<?php

namespace App\Services;

use App\Mail\GenericMail;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * @param  array<string>       $to
     * @param  array<string>|null  $cc
     * @param  array<string>|null  $bcc
     * @param  array<array{name: string, content: string, mime: string}>  $attachments
     */
    public function send(
        array   $to,
        ?array  $cc,
        ?array  $bcc,
        string  $subject,
        ?string $textBody,
        ?string $htmlBody,
        array   $attachments,
    ): void {
        $mailable = new GenericMail(
            emailSubject:     $subject,
            textBody:         $textBody,
            htmlBody:         $htmlBody,
            emailAttachments: $attachments,
        );

        $mailer = Mail::to($to);

        if (!empty($cc)) {
            $mailer = $mailer->cc($cc);
        }

        if (!empty($bcc)) {
            $mailer = $mailer->bcc($bcc);
        }

        $mailer->send($mailable);
    }
}
