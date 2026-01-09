<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentAttachmentFactory extends Factory
{
    protected $model = DocumentAttachment::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'file_name' => Str::random(40).'.pdf',
            'original_name' => 'attachment.pdf',
            'file_path' => 'attachments/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1_000, 5_000_000),
            'file_hash' => Str::random(64),
            'uploaded_by' => User::factory(),
            'upload_ip_address' => fake()->ipv4(),
            'upload_user_agent' => fake()->userAgent(),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'version' => 1,
            'status' => 'active',
            'replaced_by_id' => null,
            'deleted_by' => null,
            'uploaded_at' => now(),
        ];
    }
}
