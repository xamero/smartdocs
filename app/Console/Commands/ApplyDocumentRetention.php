<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\SystemConfiguration;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ApplyDocumentRetention extends Command
{
    protected $signature = 'documents:apply-retention';

    protected $description = 'Apply document retention rules and archive eligible documents';

    public function handle()
    {
        $retentionDays = $this->resolveRetentionDays();

        $this->info("Applying retention: {$retentionDays} days for completed documents.");

        $thresholdDate = now()->subDays($retentionDays)->startOfDay();

        $query = Document::query()
            ->where('status', 'completed')
            ->where('is_archived', false)
            ->whereDate('date_received', '<=', $thresholdDate);

        $total = 0;

        $query->chunkById(100, function ($documents) use (&$total) {
            /** @var \App\Models\Document $document */
            foreach ($documents as $document) {
                $document->update([
                    'status' => 'archived',
                    'is_archived' => true,
                    'archived_at' => now(),
                ]);

                $total++;
            }
        });

        $this->info("Archived {$total} document(s) based on retention policy.");

        return self::SUCCESS;
    }

    protected function resolveRetentionDays(): int
    {
        /** @var SystemConfiguration|null $config */
        $config = SystemConfiguration::query()
            ->where('key', 'retention.default_days')
            ->first();

        if (! $config || $config->value === null) {
            return 365;
        }

        $days = (int) $config->value;

        return $days > 0 ? $days : 365;
    }
}
