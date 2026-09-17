<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class GenericMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        private readonly string $emailSubject,
        private readonly ?string $textBody,
        private readonly ?string $htmlBody,
        private readonly array $emailAttachments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
            using: function (Email $message): void {
                foreach ($this->inlineAttachments() as $attachment) {
                    $content = base64_decode($attachment['content'], strict: true);
                    if ($content === false) {
                        throw new InvalidArgumentException(
                            "Invalid Base64 content for inline attachment {$attachment['name']}."
                        );
                    }

                    $message->addPart(
                        (new DataPart($content, $attachment['name'], $attachment['mime']))
                            ->asInline()
                            ->setContentId($attachment['content_id'])
                    );
                }
            },
        );
    }

    public function content(): Content
    {
        if ($this->htmlBody !== null) {
            return new Content(
                view: 'emails.generic',
                with: [
                    'subject' => $this->emailSubject,
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
                fn () => base64_decode($a['content'], strict: true),
                $a['name'],
            )->withMime($a['mime']),
            array_values(array_filter(
                $this->emailAttachments,
                fn (array $attachment) => ($attachment['disposition'] ?? 'attachment') !== 'inline',
            )),
        );
    }

    private function inlineAttachments(): array
    {
        return array_values(array_filter(
            $this->emailAttachments,
            fn (array $attachment) => ($attachment['disposition'] ?? 'attachment') === 'inline',
        ));
    }
}
