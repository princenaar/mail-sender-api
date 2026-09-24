<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailLogsAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mail_logs_password' => 'test-password']);
    }

    public function test_logs_redirect_to_login_when_not_authenticated(): void
    {
        $this->get('/logs')->assertRedirect('/logs/login');
    }

    public function test_login_page_is_available_when_password_is_configured(): void
    {
        $this->get('/logs/login')->assertOk()->assertSee('name="password"', false);
    }

    public function test_logs_are_unavailable_when_password_is_not_configured(): void
    {
        config(['services.mail_logs_password' => '']);

        $this->get('/logs')->assertStatus(503);
        $this->get('/logs/login')->assertStatus(503);
    }

    public function test_incorrect_password_does_not_authenticate(): void
    {
        $this->post('/logs/login', ['password' => 'incorrect'])
            ->assertStatus(422)
            ->assertSee('The password is incorrect.');

        $this->get('/logs')->assertRedirect('/logs/login');
    }

    public function test_correct_password_authenticates_and_redirects_to_logs(): void
    {
        $this->post('/logs/login', ['password' => 'test-password'])
            ->assertRedirect(route('logs.index'))
            ->assertSessionHas('mail_logs_authenticated', true);

        $this->get('/logs')->assertOk();
    }

    public function test_logout_clears_authentication(): void
    {
        $this->withSession(['mail_logs_authenticated' => true])
            ->post('/logs/logout')
            ->assertRedirect('/logs/login')
            ->assertSessionMissing('mail_logs_authenticated');

        $this->get('/logs')->assertRedirect('/logs/login');
    }
}
