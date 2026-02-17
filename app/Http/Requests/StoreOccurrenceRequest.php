<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOccurrenceRequest extends FormRequest
{
    /**
     * Determina se o usuário pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true; // ou lógica de permissão adicional
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            // campos da ocorrência que devem ser validados
            "externalId"  => "required|string|unique:occurrences,external_id",
            "description" => "required|string",
            "type"        => "required|string",
            "reportedAt"  => "required|date",
        ];
    }

    /**
     * Mensagens customizadas (opcional).
     */
    public function messages(): array
    {
        return [
            "externalId.required" => "O identificador externo da ocorrência é obrigatório.",
            "externalId.unique"   => "A ocorrência informada já foi registrada.",
            "reportedAt.required" => "A data/hora de reporte da ocorrência é obrigatória.",
        ];
    }
}
