<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentAction>
 */
class DocumentActionFactory extends Factory
{
    protected $model = \App\Models\DocumentAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'office_id' => Office::factory(),
            'action_by' => User::factory(),
            'action_type' => fake()->randomElement(['approve', 'note', 'comply', 'sign', 'return', 'forward']),
            'remarks' => fake()->optional()->paragraph(),
            'memo_file_path' => null,
            'is_office_head_approval' => false,
            'action_at' => now(),
        ];
    }

    public function approve(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'approve',
        ]);
    }

    public function note(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'note',
        ]);
    }

    public function comply(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'comply',
        ]);
    }

    public function sign(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'sign',
        ]);
    }

    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'return',
        ]);
    }

    public function forward(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'forward',
        ]);
    }

    public function withOfficeHeadApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_office_head_approval' => true,
        ]);
    }
}
