<?php

namespace Tests\Feature;

use App\Enums\MailStatus;
use App\Models\MailLog;
use App\Support\MailLogsAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mail_logs_password' => 'test-password']);
        $this->withSession([MailLogsAuthentication::SESSION_KEY => MailLogsAuthentication::fingerprint()]);
    }

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

    public function test_logs_table_includes_all_recipients_for_datatables_search(): void
    {
        MailLog::factory()->create([
            'to' => ['to@example.com'],
            'cc' => ['cc@example.com'],
            'bcc' => ['bcc@example.com'],
        ]);

        $this->get('/logs')
            ->assertSee('to@example.com')
            ->assertSee('cc@example.com')
            ->assertSee('bcc@example.com')
            ->assertSee("new DataTable('#mail-logs'", false)
            ->assertSee("targets: [0, 2, 3, 4, 5, 6], searchable: false", false);
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

    public function test_logs_page_displays_specific_mail_failure_reason(): void
    {
        MailLog::factory()->failed()->create([
            'error_message' => '550 5.1.1 user unknown',
        ]);

        $this->get('/logs')->assertSee('Adresse du destinataire inexistante.');
    }

    public function test_logs_page_falls_back_to_original_mail_error(): void
    {
        MailLog::factory()->failed()->create([
            'error_message' => 'SMTP connection refused',
        ]);

        $this->get('/logs')->assertSee('SMTP connection refused');
    }

    public function test_logs_page_displays_mailbox_full_reason(): void
    {
        MailLog::factory()->failed()->create([
            'error_message' => '452 5.2.2 mailbox full',
        ]);

        $this->get('/logs')->assertSee('Boîte de réception pleine (quota dépassé).');
    }

    public function test_empty_logs_page_shows_no_records_message(): void
    {
        $this->get('/logs')->assertSee('No mail logs found');
    }

    public function test_datatables_receives_all_logs_for_client_side_pagination(): void
    {
        MailLog::factory()->count(29)->create();
        MailLog::factory()->create(['subject' => 'Last DataTables Row']);

        $this->get('/logs')->assertStatus(200);
        $this->get('/logs')
            ->assertSee('Last DataTables Row')
            ->assertSee("order: [[6, 'desc']]", false);
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
