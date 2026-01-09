<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document_type' => ['required', 'string', Rule::in(['incoming', 'outgoing', 'internal'])],
            'source' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'confidentiality' => ['required', 'string', Rule::in(['public', 'confidential', 'restricted'])],
            'receiving_office_id' => ['nullable', 'exists:offices,id'],
            'date_received' => ['nullable', 'date'],
            'date_due' => ['nullable', 'date', 'after_or_equal:date_received'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The document title is required.',
            'document_type.required' => 'The document type is required.',
            'document_type.in' => 'The document type must be incoming, outgoing, or internal.',
            'priority.required' => 'The priority level is required.',
            'priority.in' => 'The priority must be low, normal, high, or urgent.',
            'confidentiality.required' => 'The confidentiality level is required.',
            'confidentiality.in' => 'The confidentiality must be public, confidential, or restricted.',
            'receiving_office_id.exists' => 'The selected receiving office does not exist.',
            'date_due.after_or_equal' => 'The due date must be on or after the received date.',
        ];
    }
}
