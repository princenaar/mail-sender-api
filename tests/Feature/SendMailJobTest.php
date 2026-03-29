<?php

namespace Tests\Feature;

use App\Enums\MailStatus;
use App\Jobs\SendMailJob;
use App\Mail\GenericMail;
use App\Models\MailLog;
use App\Services\MailService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendMailJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_mail_and_sets_status_to_sent(): void
    {
        Mail::fake();

        $log = MailLog::factory()->create([
            'to'        => ['alice@example.com'],
            'subject'   => 'Test Subject',
            'text_body' => 'Hello',
            'status'    => MailStatus::Pending,
        ]);

        (new SendMailJob($log->id))->handle(new MailService());

        $log->refresh();

        $this->assertEquals(MailStatus::Sent, $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertNull($log->error_message);
        Mail::assertSent(GenericMail::class, fn ($mail) => $mail->hasTo('alice@example.com'));
    }

    public function test_handle_sets_sent_at_timestamp(): void
    {
        Mail::fake();

        $log = MailLog::factory()->create([
            'to'      => ['alice@example.com'],
            'subject' => 'Timestamp Test',
            'html_body' => '<p>Hi</p>',
        ]);

        $before = now()->floorSecond();
        (new SendMailJob($log->id))->handle(new MailService());
        $after = now()->ceilSecond();

        $log->refresh();

        $this->assertTrue($log->sent_at->between($before, $after));
    }

    public function test_failed_sets_status_to_failed_with_error_message(): void
    {
        $log = MailLog::factory()->create([
            'to'        => ['alice@example.com'],
            'subject'   => 'Failure Test',
            'text_body' => 'Body',
        ]);

        $job = new SendMailJob($log->id);
        $job->failed(new Exception('SMTP connection refused'));

        $log->refresh();

        $this->assertEquals(MailStatus::Failed, $log->status);
        $this->assertEquals('SMTP connection refused', $log->error_message);
        $this->assertNull($log->sent_at);
    }

    public function test_failed_does_not_throw_when_log_not_found(): void
    {
        $this->expectNotToPerformAssertions();

        // Verifies that failed() uses where()->update() not findOrFail()
        $job = new SendMailJob(99999);
        $job->failed(new Exception('Some error'));
    }

    public function test_job_has_correct_retry_configuration(): void
    {
        $log = MailLog::factory()->create();
        $job = new SendMailJob($log->id);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 60], $job->backoff);
        $this->assertEquals(30, $job->timeout);
    }
}
