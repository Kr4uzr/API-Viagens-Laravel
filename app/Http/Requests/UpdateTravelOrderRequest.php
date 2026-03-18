<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização dos detalhes do pedido.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination.required' => 'O destino é obrigatório.',
            'destination.max' => 'O destino deve ter no máximo 255 caracteres.',
            'departure_date.required' => 'A data de ida é obrigatória.',
            'departure_date.date' => 'A data de ida deve ser uma data válida.',
            'departure_date.after_or_equal' => 'A data de ida deve ser hoje ou uma data futura.',
            'return_date.required' => 'A data de retorno é obrigatória.',
            'return_date.date' => 'A data de retorno deve ser uma data válida.',
            'return_date.after_or_equal' => 'A data de retorno deve ser igual ou posterior à data de ida.',
        ];
    }
}

