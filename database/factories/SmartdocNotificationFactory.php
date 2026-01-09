<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Office;
use App\Models\SmartdocNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmartdocNotification>
 */
class SmartdocNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['routing', 'action_required', 'overdue', 'qr_scan', 'priority_escalation'];

        return [
            'user_id' => User::factory(),
            'office_id' => Office::factory(),
            'document_id' => Document::factory(),
            'type' => fake()->randomElement($types),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'data' => null,
            'is_read' => false,
            'read_at' => null,
            'is_email_sent' => false,
            'email_sent_at' => null,
        ];
    }
}
