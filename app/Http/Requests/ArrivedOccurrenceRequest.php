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
            'resourceCode' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'occurrenceId.required' => 'O identificador da ocorrência é obrigatório.',
            'occurrenceId.exists' => 'A ocorrência informada não existe.',
            'resourceCode.required' => 'O código do recurso despachado é obrigatório.',
        ];
    }
}
