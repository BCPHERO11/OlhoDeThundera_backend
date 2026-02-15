<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispatchOccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'occurrenceId' => 'required|uuid|exists:occurrences,id',
            'resourceCode' => 'required|string|max:50',
            'assignedAt' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'occurrenceId.required' => 'O identificador da ocorrência é obrigatório.',
            'occurrenceId.exists' => 'A ocorrência informada não existe.',
            'resourceCode.required' => 'O código do recurso despachado é obrigatório.',
            'assignedAt.required' => 'A data/hora do despacho é obrigatória.',
        ];
    }
}
