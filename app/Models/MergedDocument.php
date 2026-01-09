<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MergedDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'master_tracking_number',
        'title',
        'description',
        'file_path',
        'file_type',
        'created_by',
        'current_office_id',
        'embedded_qr_metadata',
        'is_archived',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'embedded_qr_metadata' => 'array',
            'is_archived' => 'boolean',
            'archived_at' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'current_office_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MergedDocumentItem::class);
    }
}
