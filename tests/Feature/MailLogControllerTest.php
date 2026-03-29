<?php

namespace Tests\Feature;

use App\Enums\MailStatus;
use App\Models\MailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_page_returns_200(): void
    {
        $this->get('/logs')->assertStatus(200);
    }

    public function test_logs_page_shows_mail_subject(): void
    {
        MailLog::factory()->create(['subject' => 'Unique Test Subject XYZ']);

        $this->get('/logs')->assertSee('Unique Test Subject XYZ');
    }

    public function test_logs_page_shows_recipient_email(): void
    {
        MailLog::factory()->create(['to' => ['recipient@example.com']]);

        $this->get('/logs')->assertSee('recipient@example.com');
    }

    public function test_logs_page_shows_pending_badge(): void
    {
        MailLog::factory()->create(['status' => MailStatus::Pending]);

        $this->get('/logs')->assertSee('pending');
    }

    public function test_logs_page_shows_sent_badge(): void
    {
        MailLog::factory()->sent()->create();

        $this->get('/logs')->assertSee('sent');
    }

    public function test_logs_page_shows_failed_badge(): void
    {
        MailLog::factory()->failed()->create();

        $this->get('/logs')->assertSee('failed');
    }

    public function test_empty_logs_page_shows_no_records_message(): void
    {
        $this->get('/logs')->assertSee('No mail logs found');
    }

    public function test_pagination_with_30_records_returns_200(): void
    {
        MailLog::factory()->count(30)->create();

        $this->get('/logs')->assertStatus(200);
        $this->get('/logs?page=2')->assertStatus(200);
    }

    public function test_page_2_does_not_show_all_30_subjects(): void
    {
        MailLog::factory()->count(30)->create();

        $response = $this->get('/logs?page=2');
        $response->assertStatus(200);
        $response->assertDontSee('No mail logs found');
    }

    public function test_logs_page_shows_text_body_preview(): void
    {
        MailLog::factory()->create([
            'text_body' => 'This is the plain text preview.',
            'html_body' => null,
        ]);

        $this->get('/logs')->assertSee('This is the plain text preview.');
    }

    public function test_logs_are_ordered_newest_first(): void
    {
        MailLog::factory()->create(['subject' => 'Old Email', 'created_at' => now()->subHour()]);
        MailLog::factory()->create(['subject' => 'New Email', 'created_at' => now()]);

        $content = $this->get('/logs')->getContent();

        $this->assertLessThan(
            strpos($content, 'Old Email'),
            strpos($content, 'New Email'),
        );
    }
}
