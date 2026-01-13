<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'to_office_id' => ['required_without:to_office_ids', 'exists:offices,id'],
            'to_office_ids' => ['required_without:to_office_id', 'array', 'min:1'],
            'to_office_ids.*' => ['exists:offices,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'create_copies' => ['sometimes', 'boolean'], // If true, create copies for each office
        ];
    }

    public function messages(): array
    {
        return [
            'to_office_id.required' => 'Please select a destination office.',
            'to_office_id.exists' => 'The selected office does not exist.',
        ];
    }
}
