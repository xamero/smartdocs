<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QRScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'qr_code_id',
        'document_id',
        'scanned_by_type',
        'scanned_by_user_id',
        'ip_address',
        'user_agent',
        'scan_location',
        'scan_type',
        'merged_document_id',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QRCode::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function scannedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }

    public function mergedDocument(): BelongsTo
    {
        return $this->belongsTo(MergedDocument::class);
    }
}
