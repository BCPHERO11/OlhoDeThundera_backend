<?php

namespace App\Http\Requests;

class StartOccurrenceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'occurrenceId' => $this->route('uuid'),
        ]);
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
            'occurrenceId.required' => 'O identificador da ocorrência na URL é obrigatório.',
            'occurrenceId.exists' => 'A ocorrência informada não existe.',
            'startedAt.required' => 'A data/hora de início da ocorrência é obrigatória.',
        ];
    }
}
