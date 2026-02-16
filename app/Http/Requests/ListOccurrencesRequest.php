<?php

namespace App\Http\Requests;

class ListOccurrencesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:reported,in_progress,resolved,cancelled',
            'type' => 'nullable|string',
        ];
    }
}

