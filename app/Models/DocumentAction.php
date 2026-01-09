<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'office_id',
        'action_by',
        'action_type',
        'remarks',
        'memo_file_path',
        'is_office_head_approval',
        'action_at',
    ];

    protected function casts(): array
    {
        return [
            'is_office_head_approval' => 'boolean',
            'action_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
