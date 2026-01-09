<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MergedDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'merged_document_id',
        'document_id',
        'sequence',
    ];

    public function mergedDocument(): BelongsTo
    {
        return $this->belongsTo(MergedDocument::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
