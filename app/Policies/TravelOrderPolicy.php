<?php

namespace App\Policies;

use App\Models\TravelOrder;
use App\Models\User;

class TravelOrderPolicy
{
    /**
     * Qualquer usuário autenticado pode listar seus próprios pedidos.
     * A filtragem por usuário é feita no repositório.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Permite visualizar apenas pedidos do próprio usuário.
     *
     * @param User $user
     * @param TravelOrder $order
     * @return bool
     */
    public function view(User $user, TravelOrder $order): bool
    {
        return $user->id === $order->user_id;
    }

    /**
     * Permite alterar o status de um pedido somente se o usuário
     * NÃO for o dono do pedido.
     *
     * @param User $user
     * @param TravelOrder $order
     * @return bool
     */
    public function updateStatus(User $user, TravelOrder $order): bool
    {
        return $user->id !== $order->user_id;
    }

    /**
     * Permite ao dono alterar os detalhes do pedido (destino/datas).
     * A regra de "apenas antes de aprovar" é validada na camada de service.
     */
    public function updateDetails(User $user, TravelOrder $order): bool
    {
        return $user->id === $order->user_id;
    }
}
