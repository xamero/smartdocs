<?php

namespace App\Services;

use App\Models\Document;

class TrackingNumberService
{
    public function generate(string $documentType = 'incoming', ?string $prefix = null): string
    {
        $prefix = $prefix ?? $this->getDefaultPrefix($documentType);
        $year = now()->format('Y');
        $sequence = $this->getNextSequence($prefix, $year);

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    protected function getDefaultPrefix(string $documentType): string
    {
        return match ($documentType) {
            'incoming' => 'IN',
            'outgoing' => 'OUT',
            'internal' => 'INT',
            default => 'DOC',
        };
    }

    protected function getNextSequence(string $prefix, string $year): int
    {
        $lastDocument = Document::where('tracking_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('tracking_number', 'desc')
            ->first();

        if ($lastDocument) {
            $parts = explode('-', $lastDocument->tracking_number);
            $lastSequence = (int) end($parts);

            return $lastSequence + 1;
        }

        return 1;
    }

    public function validate(string $trackingNumber): bool
    {
        return (bool) preg_match('/^[A-Z]{2,4}-\d{4}-\d{6}$/', $trackingNumber);
    }
}
