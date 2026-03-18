<?php

namespace App\Notifications;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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

    /**
     * Representação da notificação para envio por e-mail.
     * Enviado para o e-mail cadastrado do usuário dono do pedido.
     *
     * @param object $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = match ($this->order->status) {
            TravelOrderStatus::Approved => 'aprovado',
            TravelOrderStatus::Cancelled => 'cancelado',
            TravelOrderStatus::Requested => 'solicitado',
        };

        return (new MailMessage)
            ->subject('Status do seu pedido de viagem foi atualizado')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O status do seu pedido de viagem foi alterado para **{$statusLabel}**.")
            ->line("**Destino:** {$this->order->destination}")
            ->line("**Ida:** {$this->order->departure_date->format('d/m/Y')} | **Retorno:** {$this->order->return_date->format('d/m/Y')}")
            ->salutation('Att, Equipe ' . config('app.name'));
    }
}
