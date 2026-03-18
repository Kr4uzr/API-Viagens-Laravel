<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TravelOrder
 */
class TravelOrderResource extends JsonResource
{
    /**
     * Transforma o pedido de viagem em array para resposta JSON.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'solicitante' => $this->user?->name,
            'destination' => $this->destination,
            'departure_date' => $this->departure_date->format('Y-m-d'),
            'return_date' => $this->return_date->format('Y-m-d'),
            'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
