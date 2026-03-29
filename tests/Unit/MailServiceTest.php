<?php

namespace Tests\Unit;

use App\Services\MailService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailServiceTest extends TestCase
{
    private MailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->service = new MailService();
    }

    public function test_sends_html_email_using_blade_template(): void
    {
        $this->service->send(
            to: ['alice@example.com'],
            cc: null,
            bcc: null,
            subject: 'Hello',
            textBody: null,
            htmlBody: '<p>Hello</p>',
            attachments: [],
        );

        Mail::assertSent(Mailable::class, function (Mailable $mail) {
            return in_array('alice@example.com', array_column($mail->to, 'address'));
        });
    }

    public function test_sends_plain_text_email(): void
    {
        $this->service->send(
            to: ['bob@example.com'],
            cc: null,
            bcc: null,
            subject: 'Plain',
            textBody: 'Hello plain',
            htmlBody: null,
            attachments: [],
        );

        Mail::assertSent(Mailable::class, function (Mailable $mail) {
            return in_array('bob@example.com', array_column($mail->to, 'address'));
        });
    }

    public function test_sets_cc_and_bcc_when_provided(): void
    {
        $this->service->send(
            to: ['alice@example.com'],
            cc: ['cc@example.com'],
            bcc: ['bcc@example.com'],
            subject: 'CC BCC Test',
            textBody: 'body',
            htmlBody: null,
            attachments: [],
        );

        Mail::assertSent(Mailable::class, function (Mailable $mail) {
            return in_array('cc@example.com', array_column($mail->cc, 'address'))
                && in_array('bcc@example.com', array_column($mail->bcc, 'address'));
        });
    }

    public function test_sends_with_base64_attachment(): void
    {
        $this->service->send(
            to: ['alice@example.com'],
            cc: null,
            bcc: null,
            subject: 'With attachment',
            textBody: null,
            htmlBody: '<p>See attached</p>',
            attachments: [
                [
                    'name'    => 'file.pdf',
                    'content' => base64_encode('fake-pdf-content'),
                    'mime'    => 'application/pdf',
                ],
            ],
        );

        // Verifies that base64 decode + attachData runs without exception
        Mail::assertSent(Mailable::class);
    }

    public function test_sends_to_multiple_recipients(): void
    {
        $this->service->send(
            to: ['alice@example.com', 'bob@example.com'],
            cc: null,
            bcc: null,
            subject: 'Multi-recipient',
            textBody: 'Hello both',
            htmlBody: null,
            attachments: [],
        );

        Mail::assertSent(Mailable::class, function (Mailable $mail) {
            $addresses = array_column($mail->to, 'address');
            return in_array('alice@example.com', $addresses)
                && in_array('bob@example.com', $addresses);
        });
    }
}
