<?php

use App\Models\Document;
use App\Models\SystemConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('archives completed documents past retention threshold', function () {
    SystemConfiguration::create([
        'key' => 'retention.default_days',
        'value' => '30',
        'type' => 'integer',
        'group' => 'tracking',
        'is_public' => false,
    ]);

    $oldDocument = Document::factory()->create([
        'status' => 'completed',
        'is_archived' => false,
        'date_received' => now()->subDays(40),
    ]);

    $recentDocument = Document::factory()->create([
        'status' => 'completed',
        'is_archived' => false,
        'date_received' => now()->subDays(10),
    ]);

    $this->artisan('documents:apply-retention')
        ->assertExitCode(0);

    $this->assertDatabaseHas('documents', [
        'id' => $oldDocument->id,
        'is_archived' => true,
        'status' => 'archived',
    ]);

    $this->assertDatabaseHas('documents', [
        'id' => $recentDocument->id,
        'is_archived' => false,
        'status' => 'completed',
    ]);
});

