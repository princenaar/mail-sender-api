<?php

namespace Tests\Feature;

use App\Enums\MailStatus;
use App\Jobs\SendMailJob;
use App\Models\MailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-token';

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'to'        => ['alice@example.com'],
            'subject'   => 'Hello World',
            'text_body' => 'Plain text body',
        ], $overrides);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_returns_202_with_queued_status_on_valid_request(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            $this->validPayload(),
            $this->authHeaders(),
        );

        $response->assertStatus(202)
                 ->assertJsonStructure(['message', 'mail_log_id'])
                 ->assertJsonFragment(['message' => 'Mail queued for delivery.']);
    }

    public function test_creates_a_pending_mail_log(): void
    {
        Queue::fake();

        $this->postJson(
            '/api/v1/mails/send',
            $this->validPayload([
                'to'      => ['alice@example.com', 'bob@example.com'],
                'subject' => 'My Subject',
            ]),
            $this->authHeaders(),
        );

        $this->assertDatabaseHas('mail_logs', [
            'subject' => 'My Subject',
            'status'  => MailStatus::Pending->value,
        ]);

        $log = MailLog::first();
        $this->assertEquals(['alice@example.com', 'bob@example.com'], $log->to);
        $this->assertNull($log->sent_at);
    }

    public function test_dispatches_send_mail_job(): void
    {
        Queue::fake();

        $this->postJson(
            '/api/v1/mails/send',
            $this->validPayload(),
            $this->authHeaders(),
        );

        Queue::assertPushed(SendMailJob::class);
    }

    public function test_returns_401_when_authorization_header_is_missing(): void
    {
        $response = $this->postJson('/api/v1/mails/send', $this->validPayload());

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Unauthorized.']);
    }

    public function test_returns_401_when_token_is_wrong(): void
    {
        $response = $this->postJson(
            '/api/v1/mails/send',
            $this->validPayload(),
            ['Authorization' => 'Bearer wrong-token'],
        );

        $response->assertStatus(401);
    }

    public function test_returns_422_when_to_is_missing(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            ['subject' => 'No recipient', 'text_body' => 'body'],
            $this->authHeaders(),
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['to']);
    }

    public function test_returns_422_when_to_contains_invalid_email(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            $this->validPayload(['to' => ['not-an-email']]),
            $this->authHeaders(),
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['to.0']);
    }

    public function test_returns_422_when_subject_is_missing(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            ['to' => ['alice@example.com'], 'text_body' => 'body'],
            $this->authHeaders(),
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['subject']);
    }

    public function test_returns_422_when_both_body_fields_are_missing(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            ['to' => ['alice@example.com'], 'subject' => 'No body'],
            $this->authHeaders(),
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['text_body', 'html_body']);
    }

    public function test_accepts_html_body_without_text_body(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            ['to' => ['alice@example.com'], 'subject' => 'HTML only', 'html_body' => '<p>Hi</p>'],
            $this->authHeaders(),
        );

        $response->assertStatus(202);
    }

    public function test_returns_422_when_cc_contains_invalid_email(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            $this->validPayload(['cc' => ['not-valid']]),
            $this->authHeaders(),
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['cc.0']);
    }

    public function test_returns_422_when_attachment_missing_required_fields(): void
    {
        Queue::fake();

        $response = $this->postJson(
            '/api/v1/mails/send',
            $this->validPayload([
                'attachments' => [['name' => 'file.pdf']], // missing content + mime
            ]),
            $this->authHeaders(),
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['attachments.0.content', 'attachments.0.mime']);
    }

    public function test_stores_html_body_in_mail_log(): void
    {
        Queue::fake();

        $this->postJson(
            '/api/v1/mails/send',
            ['to' => ['alice@example.com'], 'subject' => 'HTML Test', 'html_body' => '<h1>Hello</h1>'],
            $this->authHeaders(),
        );

        $this->assertDatabaseHas('mail_logs', [
            'html_body' => '<h1>Hello</h1>',
            'subject'   => 'HTML Test',
        ]);
    }
}
