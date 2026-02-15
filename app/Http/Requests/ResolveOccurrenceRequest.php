<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveOccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'occurrenceId' => 'required|uuid|exists:occurrences,id',
            'resolvedAt' => 'required|date',
            'resolutionNotes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'occurrenceId.required' => 'O identificador da ocorrência é obrigatório.',
            'occurrenceId.exists' => 'A ocorrência informada não existe.',
            'resolvedAt.required' => 'A data/hora de resolução da ocorrência é obrigatória.',
        ];
    }
}
