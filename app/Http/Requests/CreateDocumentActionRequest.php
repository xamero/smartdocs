<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDocumentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'action_type' => ['required', 'string', Rule::in(['approve', 'note', 'comply', 'sign', 'return', 'forward'])],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'memo_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'is_office_head_approval' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'action_type.required' => 'Please select an action type.',
            'action_type.in' => 'The action type must be approve, note, comply, sign, return, or forward.',
            'memo_file.mimes' => 'The memo file must be a PDF or Word document.',
            'memo_file.max' => 'The memo file must not exceed 10MB.',
        ];
    }
}
