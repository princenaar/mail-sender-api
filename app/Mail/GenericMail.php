<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string  $emailSubject,
        private readonly ?string $textBody,
        private readonly ?string $htmlBody,
        private readonly array   $emailAttachments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        if ($this->htmlBody !== null) {
            return new Content(
                view: 'emails.generic',
                with: [
                    'subject'     => $this->emailSubject,
                    'htmlContent' => $this->htmlBody,
                ],
            );
        }

        return new Content(
            text: 'emails.generic_text',
            with: ['textContent' => $this->textBody],
        );
    }

    public function attachments(): array
    {
        return array_map(
            fn (array $a) => Attachment::fromData(
                fn () => base64_decode($a['content']),
                $a['name'],
            )->withMime($a['mime']),
            $this->emailAttachments,
        );
    }
}
