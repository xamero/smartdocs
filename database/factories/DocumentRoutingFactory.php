<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentRouting>
 */
class DocumentRoutingFactory extends Factory
{
    protected $model = \App\Models\DocumentRouting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'from_office_id' => Office::factory(),
            'to_office_id' => Office::factory(),
            'routed_by' => User::factory(),
            'received_by' => null,
            'remarks' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'in_transit', 'received', 'returned']),
            'routed_at' => now(),
            'received_at' => null,
            'returned_at' => null,
            'sequence' => 1,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'received_at' => null,
            'received_by' => null,
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
            'received_at' => now(),
            'received_by' => User::factory(),
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_transit',
        ]);
    }
}
