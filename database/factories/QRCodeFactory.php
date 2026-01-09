<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QRCode>
 */
class QRCodeFactory extends Factory
{
    protected $model = \App\Models\QRCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = Str::random(32);
        $trackingNumber = fake()->regexify('[A-Z]{2,4}-\d{4}-\d{6}');

        return [
            'document_id' => Document::factory(),
            'code' => $code,
            'hash' => hash('sha256', $trackingNumber.$code.now()->timestamp),
            'verification_url' => url('/documents/verify/'.$code),
            'image_path' => 'qr-codes/'.fake()->uuid().'.png',
            'metadata' => [
                'tracking_number' => $trackingNumber,
                'title' => fake()->sentence(),
                'status' => fake()->randomElement(['registered', 'in_transit', 'received']),
                'generated_at' => now()->toIso8601String(),
            ],
            'is_active' => true,
            'is_regenerated' => false,
            'regenerated_from_id' => null,
            'scan_count' => 0,
            'last_scanned_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function regenerated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_regenerated' => true,
        ]);
    }
}
