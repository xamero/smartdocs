<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document_type' => ['sometimes', 'required', 'string', Rule::in(['incoming', 'outgoing', 'internal'])],
            'source' => ['nullable', 'string', 'max:255'],
            'priority' => ['sometimes', 'required', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'confidentiality' => ['sometimes', 'required', 'string', Rule::in(['public', 'confidential', 'restricted'])],
            'status' => ['sometimes', 'required', 'string', Rule::in(['draft', 'registered', 'in_transit', 'received', 'in_action', 'completed', 'archived', 'returned'])],
            'date_received' => ['nullable', 'date'],
            'date_due' => ['nullable', 'date', 'after_or_equal:date_received'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
