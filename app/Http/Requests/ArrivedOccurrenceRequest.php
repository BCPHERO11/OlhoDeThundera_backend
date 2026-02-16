<?php

namespace App\Http\Requests;

class ArrivedOccurrenceRequest extends ApiFormRequest
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
            'dispatchId' => 'required|uuid|exists:dispatches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'occurrenceId.required' => 'O identificador da ocorrência é obrigatório.',
            'occurrenceId.exists' => 'A ocorrência informada não existe.',
            'dispatchId.required' => 'O identificador do dispatch é obrigatório.',
            'dispatchId.exists' => 'O dispatch informado não existe.',
        ];
    }
}
