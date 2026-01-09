<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_hash',
        'uploaded_by',
        'upload_ip_address',
        'upload_user_agent',
        'description',
        'sort_order',
        'version',
        'status',
        'replaced_by_id',
        'deleted_by',
        'uploaded_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(DocumentAttachment::class, 'replaced_by_id');
    }

    public function replaces(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class, 'replaced_by_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deleted_at === null;
    }

    public function isReplaced(): bool
    {
        return $this->status === 'replaced';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNull('deleted_at');
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }
}
