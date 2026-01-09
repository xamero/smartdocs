<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'qr_codes';

    protected $fillable = [
        'document_id',
        'code',
        'hash',
        'verification_url',
        'image_path',
        'metadata',
        'is_active',
        'is_regenerated',
        'regenerated_from_id',
        'scan_count',
        'last_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'is_regenerated' => 'boolean',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function regeneratedFrom(): BelongsTo
    {
        return $this->belongsTo(QRCode::class, 'regenerated_from_id');
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(QRScanLog::class);
    }
}
