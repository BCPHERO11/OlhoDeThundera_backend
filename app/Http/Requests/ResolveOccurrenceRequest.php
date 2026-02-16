<?php

namespace App\Http\Requests;

class ResolveOccurrenceRequest extends ApiFormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'occurrenceId.required' => 'O identificador da ocorrência na URL é obrigatório.',
            'occurrenceId.exists' => 'A ocorrência informada não existe.',
        ];
    }
}
