<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TravelOrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TravelOrder $order
    ) {}

    /**
     * Define os canais de entrega da notificação.
     *
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Representação da notificação para armazenamento no banco.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'travel_order_id' => $this->order->id,
            'status' => $this->order->status->value,
            'destination' => $this->order->destination,
        ];
    }
}
