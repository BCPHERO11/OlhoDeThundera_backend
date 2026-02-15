<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartOccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'occurrenceId' => 'required|uuid|exists:occurrences,id',
            'startedAt' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'occurrenceId.required' => 'O identificador da ocorrência é obrigatório.',
            'occurrenceId.exists' => 'A ocorrência informada não existe.',
            'startedAt.required' => 'A data/hora de início da ocorrência é obrigatória.',
        ];
    }
}
