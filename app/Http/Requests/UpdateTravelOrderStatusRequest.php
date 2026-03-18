<?php

namespace App\Http\Requests;

use App\Enums\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTravelOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de status de pedido de viagem.
     * Aceita apenas 'approved' ou 'cancelled'.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    TravelOrderStatus::Approved->value,
                    TravelOrderStatus::Cancelled->value,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser "approved" ou "cancelled".',
        ];
    }
}
