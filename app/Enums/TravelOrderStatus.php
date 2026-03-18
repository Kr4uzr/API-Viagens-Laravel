<?php

namespace App\Enums;

/**
 * Enum para os status de um pedido de viagem.
 *
 * @package App\Enums
 */
enum TravelOrderStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
}
