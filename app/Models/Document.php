<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tracking_number',
        'title',
        'description',
        'document_type',
        'source',
        'priority',
        'confidentiality',
        'status',
        'current_office_id',
        'receiving_office_id',
        'created_by',
        'registered_by',
        'date_received',
        'date_due',
        'is_merged',
        'is_archived',
        'archived_at',
        'metadata',
        'parent_document_id',
        'is_copy',
        'copy_number',
    ];

    protected function casts(): array
    {
        return [
            'date_received' => 'date',
            'date_due' => 'date',
            'archived_at' => 'date',
            'is_merged' => 'boolean',
            'is_archived' => 'boolean',
            'is_copy' => 'boolean',
            'copy_number' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function currentOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'current_office_id');
    }

    public function receivingOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'receiving_office_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function routings(): HasMany
    {
        return $this->hasMany(DocumentRouting::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DocumentAction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(QRCode::class);
    }

    public function mergedDocumentItems(): HasMany
    {
        return $this->hasMany(MergedDocumentItem::class);
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    /**
     * Get main document (either self if main, or parent if copy)
     */
    public function getMainDocumentAttribute(): Document
    {
        return $this->is_copy && $this->parentDocument ? $this->parentDocument : $this;
    }

    /**
     * Get all routings including main document and copies
     */
    public function getAllRoutings(): \Illuminate\Database\Eloquent\Collection
    {
        $routings = $this->routings;

        if (! $this->is_copy) {
            // If main document, also get routings from all copies
            foreach ($this->copies as $copy) {
                $routings = $routings->merge($copy->routings);
            }
        }

        return $routings->sortBy('routed_at');
    }
}
