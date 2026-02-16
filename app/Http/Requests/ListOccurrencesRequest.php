<?php

namespace App\Http\Requests;

class ListOccurrencesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->route('status') ?? $this->query('status'),
            'type' => $this->route('type') ?? $this->query('type'),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:reported,in_progress,resolved,cancelled',
            'type' => 'nullable|string',
        ];
    }
}
