<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Office;
use App\Models\User;
use App\Services\QRCodeService;
use App\Services\TrackingNumberService;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $trackingService = app(TrackingNumberService::class);
        $qrCodeService = app(QRCodeService::class);
        $offices = Office::all();
        $users = User::all();

        if ($offices->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Please seed offices and users first.');

            return;
        }

        // Create sample documents
        $documents = [
            [
                'title' => 'Request for Budget Allocation - Q1 2025',
                'document_type' => 'incoming',
                'source' => 'Provincial Capitol',
                'priority' => 'high',
                'status' => 'in_action',
                'description' => 'Request for quarterly budget allocation for infrastructure projects.',
            ],
            [
                'title' => 'Approval of Infrastructure Project Proposal',
                'document_type' => 'internal',
                'priority' => 'urgent',
                'status' => 'received',
                'description' => 'Proposal for road improvement project in Barangay Central.',
            ],
            [
                'title' => 'Monthly Financial Report - December 2024',
                'document_type' => 'outgoing',
                'source' => 'Treasurer\'s Office',
                'priority' => 'normal',
                'status' => 'completed',
                'description' => 'Monthly financial report submitted to the Provincial Treasurer.',
            ],
            [
                'title' => 'Health Program Implementation Plan',
                'document_type' => 'internal',
                'priority' => 'high',
                'status' => 'in_transit',
                'description' => 'Implementation plan for community health programs.',
            ],
            [
                'title' => 'Request for Personnel Assignment',
                'document_type' => 'incoming',
                'source' => 'HR Office',
                'priority' => 'normal',
                'status' => 'registered',
                'description' => 'Request for assignment of new personnel to various departments.',
            ],
        ];

        foreach ($documents as $docData) {
            $document = Document::create([
                'tracking_number' => $trackingService->generate($docData['document_type']),
                'title' => $docData['title'],
                'description' => $docData['description'] ?? null,
                'document_type' => $docData['document_type'],
                'source' => $docData['source'] ?? null,
                'priority' => $docData['priority'],
                'confidentiality' => fake()->randomElement(['public', 'confidential']),
                'status' => $docData['status'],
                'current_office_id' => $offices->random()->id,
                'receiving_office_id' => $offices->random()->id,
                'created_by' => $users->random()->id,
                'registered_by' => $users->random()->id,
                'date_received' => fake()->dateTimeBetween('-30 days', 'now'),
                'date_due' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            ]);

            // Generate QR code for each document
            $qrCodeService->generateForDocument($document);
        }

        $this->command->info('Created '.count($documents).' sample documents with QR codes.');
    }
}
