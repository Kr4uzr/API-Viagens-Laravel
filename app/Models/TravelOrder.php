<?php

namespace App\Models;

use App\Enums\TravelOrderStatus;
use Database\Factories\TravelOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'destination', 'departure_date', 'return_date', 'status'])]
class TravelOrder extends Model
{
    /** @use HasFactory<TravelOrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
            'status' => TravelOrderStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
