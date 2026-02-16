<?php

namespace App\Http\Requests;

class CreateOccurrenceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'externalId' => 'required|string|unique:occurrences,external_id',
            'description' => 'required|string',
            'type' => 'required|string',
            'reportedAt' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'externalId.required' => 'O identificador externo da ocorrência é obrigatório.',
            'externalId.unique' => 'A ocorrência informada já foi registrada.',
            'reportedAt.required' => 'A data/hora de reporte da ocorrência é obrigatória.',
        ];
    }
}
