<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Office;
use App\Models\User;
use App\Services\TrackingNumberService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $documentType = fake()->randomElement(['incoming', 'outgoing', 'internal']);

        // Generate unique tracking number for tests
        $prefix = match ($documentType) {
            'incoming' => 'IN',
            'outgoing' => 'OUT',
            'internal' => 'INT',
            default => 'DOC',
        };
        $year = now()->format('Y');
        $uniqueSequence = fake()->unique()->numberBetween(1, 999999);
        $trackingNumber = sprintf('%s-%s-%06d', $prefix, $year, $uniqueSequence);

        return [
            'tracking_number' => $trackingNumber,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'document_type' => $documentType,
            'source' => fake()->optional()->company(),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'confidentiality' => fake()->randomElement(['public', 'confidential', 'restricted']),
            'status' => fake()->randomElement(['draft', 'registered', 'in_transit', 'received', 'in_action', 'completed']),
            'current_office_id' => Office::factory(),
            'receiving_office_id' => Office::factory(),
            'created_by' => User::factory(),
            'registered_by' => User::factory(),
            'date_received' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'date_due' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'is_merged' => false,
            'is_archived' => false,
            'metadata' => null,
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'incoming',
        ]);
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'outgoing',
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'internal',
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
            'archived_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => 'archived',
        ]);
    }
}
