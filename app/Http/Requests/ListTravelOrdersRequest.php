<?php

namespace App\Http\Requests;

use App\Enums\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTravelOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para filtros de listagem de pedidos de viagem.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(array_column(TravelOrderStatus::cases(), 'value'))],
            'destination' => ['sometimes', 'string', 'max:255'],
            'departure_from' => ['sometimes', 'date'],
            'departure_until' => ['sometimes', 'date', 'after_or_equal:departure_from'],
            'return_from' => ['sometimes', 'date'],
            'return_until' => ['sometimes', 'date', 'after_or_equal:return_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'O status informado é inválido.',
            'departure_until.after_or_equal' => 'A data final de ida deve ser igual ou posterior à data inicial.',
            'return_until.after_or_equal' => 'A data final de retorno deve ser igual ou posterior à data inicial.',
            'per_page.min' => 'A quantidade por página deve ser no mínimo 1.',
            'per_page.max' => 'A quantidade por página deve ser no máximo 100.',
        ];
    }

    /**
     * Retorna apenas os filtros validados relevantes para a consulta.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->safe()->only([
            'status',
            'destination',
            'departure_from',
            'departure_until',
            'return_from',
            'return_until',
            'per_page',
        ]);
    }
}
