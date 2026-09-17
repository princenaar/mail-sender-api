<?php

namespace Tests\Unit;

use App\Mail\GenericMail;
use App\Services\MailService;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MailServiceTest extends TestCase
{
    private MailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->service = new MailService;
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

        Mail::assertSent(GenericMail::class, function (GenericMail $mail) {
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

        Mail::assertSent(GenericMail::class, function (GenericMail $mail) {
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

        Mail::assertSent(GenericMail::class, function (GenericMail $mail) {
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
                    'name' => 'file.pdf',
                    'content' => base64_encode('fake-pdf-content'),
                    'mime' => 'application/pdf',
                ],
            ],
        );

        // Verifies that base64 decode + attachData runs without exception
        Mail::assertSent(GenericMail::class);
    }

    public function test_builds_inline_attachment_with_explicit_content_id(): void
    {
        $mail = new GenericMail(
            emailSubject: 'Inline logo',
            textBody: null,
            htmlBody: '<img src="cid:mirsas-logo@mirsas" alt="Logo MIRSAS">',
            emailAttachments: [[
                'name' => 'banner_mirsas.png',
                'content' => base64_encode('fake-png-content'),
                'mime' => 'image/png',
                'disposition' => 'inline',
                'content_id' => 'mirsas-logo@mirsas',
            ]],
        );
        $message = (new Email)
            ->from('sender@example.com')
            ->to('alice@example.com')
            ->subject('Inline logo')
            ->html('<img src="cid:mirsas-logo@mirsas" alt="Logo MIRSAS">');

        foreach ($mail->envelope()->using as $callback) {
            $callback($message);
        }

        $attachments = $message->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('inline', $attachments[0]->getDisposition());
        $this->assertSame('mirsas-logo@mirsas', $attachments[0]->getContentId());
        $this->assertSame('image', $attachments[0]->getMediaType());
        $this->assertSame('png', $attachments[0]->getMediaSubtype());
        $this->assertEmpty($mail->attachments());
        $this->assertStringContainsString('src="cid:mirsas-logo@mirsas"', $message->getHtmlBody());

        $mime = $message->toString();
        $this->assertStringContainsString('Content-Disposition: inline', $mime);
        $this->assertStringContainsString('Content-ID: <mirsas-logo@mirsas>', $mime);
    }

    public function test_regular_attachment_keeps_default_disposition(): void
    {
        $mail = new GenericMail(
            emailSubject: 'Regular attachment',
            textBody: 'See attached',
            htmlBody: null,
            emailAttachments: [[
                'name' => 'file.pdf',
                'content' => base64_encode('fake-pdf-content'),
                'mime' => 'application/pdf',
            ]],
        );
        $message = new Email;

        foreach ($mail->envelope()->using as $callback) {
            $callback($message);
        }

        $this->assertEmpty($message->getAttachments());
        $this->assertCount(1, $mail->attachments());
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

        Mail::assertSent(GenericMail::class, function (GenericMail $mail) {
            $addresses = array_column($mail->to, 'address');

            return in_array('alice@example.com', $addresses)
                && in_array('bob@example.com', $addresses);
        });
    }
}
