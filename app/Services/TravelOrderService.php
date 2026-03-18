<?php

namespace App\Services;

use App\Enums\TravelOrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderStatusChanged;
use App\Repositories\Contracts\TravelOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TravelOrderService
{
    public function __construct(
        private TravelOrderRepositoryInterface $repository
    ) {}

    /**
     * Cria um novo pedido de viagem vinculado ao usuário autenticado.
     * Retorna o pedido com status 'requested'.
     *
     * @param array<string, mixed> $data
     * @param User $user
     * @return TravelOrder
     */
    public function createOrder(array $data, User $user): TravelOrder
    {
        return $this->repository->create([
            ...$data,
            'user_id' => $user->id,
            'status' => TravelOrderStatus::Requested,
        ]);
    }

    /**
     * Atualiza o status de um pedido de viagem e notifica o dono.
     * Valida regra de cancelamento pós-aprovação (data de ida não pode ter passado).
     * Retorna o pedido atualizado.
     *
     * @param TravelOrder $order
     * @param string $status
     * @return TravelOrder
     *
     * @throws BusinessRuleException
     */
    public function updateStatus(TravelOrder $order, string $status): TravelOrder
    {
        $newStatus = TravelOrderStatus::from($status);

        if ($order->status === TravelOrderStatus::Approved && $newStatus === TravelOrderStatus::Cancelled) {
            $this->ensureCanCancelApproved($order);
        }

        $order = $this->repository->updateStatus($order, $status);

        try {
            $order->user->notify(new TravelOrderStatusChanged($order));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar notificação de mudança de status.', [
                'travel_order_id' => $order->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }

        return $order;
    }

    /**
     * Atualiza os detalhes do pedido (destino e datas).
     *
     * Regra de negócio: somente pedidos em status "requested" podem ter
     * os detalhes alterados pelo seu solicitante.
     *
     * @param TravelOrder $order
     * @param array<string, mixed> $data
     * @return TravelOrder
     *
     * @throws BusinessRuleException
     */
    public function updateDetails(TravelOrder $order, array $data): TravelOrder
    {
        if ($order->status !== TravelOrderStatus::Requested) {
            throw new BusinessRuleException(
                'Não foi possível alterar o pedido de viagem, pois ele já foi aprovado ou cancelado!',
                409
            );
        }

        return $this->repository->updateDetails($order, $data);
    }

    /**
     * Busca um pedido de viagem pelo ID.
     * Retorna o pedido encontrado ou null.
     *
     * @param int $id
     * @return TravelOrder|null
     */
    public function getOrder(int $id): ?TravelOrder
    {
        return $this->repository->findById($id);
    }

    /**
     * Lista pedidos de viagem do usuário com filtros e paginação.
     * Retorna paginação delegada ao repositório.
     *
     * @param User $user
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function listOrders(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->listForUser($user->id, $filters);
    }

    /**
     * Valida se um pedido aprovado pode ser cancelado.
     * Lança BusinessRuleException se a data de ida já passou.
     *
     * @param TravelOrder $order
     * @return void
     *
     * @throws BusinessRuleException
     */
    private function ensureCanCancelApproved(TravelOrder $order): void
    {
        if ($order->departure_date->isPast()) {
            throw new BusinessRuleException(
                'Não foi possível cancelar o pedido de viagem, pois a data de ida já passou!'
            );
        }
    }
}
