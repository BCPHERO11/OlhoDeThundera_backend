<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalOccurrenceRequest extends FormRequest
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
            "reportedAt" => "required|date",
        ];
    }

    /**
     * Mensagens customizadas (opcional).
     */
    public function messages(): array
    {
        return [
            "externalId.required" => "O título da ocorrência é obrigatório.",
            "externalId.unique"   => "O processo informado já foi registrado"
        ];
    }

    /**
     * Preparar dados antes da validação.
     */
    protected function prepareForValidation()
    {
        // você pode sanitizar/normalizar dados aqui
        $this->merge([
            "occurred_at" => $this->input("occurred_at")
                ? date("Y-m-d H:i:s", strtotime($this->input("occurred_at")))
                : null,
        ]);
    }

    /**
     * Valida cabeçalhos antes de continuar.
     */
    protected function passedValidation()
    {
        $this->validateHeaders();
    }

    /**
     * Valida os headers obrigatórios.
     */
    protected function validateHeaders()
    {
        if (!$this->header("X-Api-Key")) {
            abort(response()->json([
                "message" => "Cabeçalho de autenticação (X-Api-Key) é obrigatório"
            ], 401));
        }

        if (!$this->header("Idempotency-Key")) {
            abort(response()->json([
                "message" => "Cabeçalho de Idempotência (Idempotency-Key) é obrigatório"
            ], 422));
        }
    }
}
