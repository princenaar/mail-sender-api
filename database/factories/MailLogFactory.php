<?php

namespace Database\Factories;

use App\Enums\MailStatus;
use App\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailLog>
 */
class MailLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'to'            => [fake()->safeEmail()],
            'cc'            => null,
            'bcc'           => null,
            'subject'       => fake()->sentence(4),
            'text_body'     => fake()->paragraph(),
            'html_body'     => null,
            'status'        => MailStatus::Pending,
            'error_message' => null,
            'sent_at'       => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'  => MailStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'        => MailStatus::Failed,
            'error_message' => fake()->sentence(),
        ]);
    }
}
