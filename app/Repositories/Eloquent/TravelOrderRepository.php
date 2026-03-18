<?php

namespace App\Repositories\Eloquent;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Repositories\Contracts\TravelOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TravelOrderRepository implements TravelOrderRepositoryInterface
{
    /**
     * Cria um novo pedido de viagem.
     * Retorna o pedido persistido no banco.
     *
     * @param array<string, mixed> $data
     * @return TravelOrder
     */
    public function create(array $data): TravelOrder
    {
        return TravelOrder::create($data);
    }

    /**
     * Busca um pedido de viagem pelo ID.
     * Retorna o pedido encontrado ou null se não existir.
     *
     * @param int $id
     * @return TravelOrder|null
     */
    public function findById(int $id): ?TravelOrder
    {
        return TravelOrder::find($id);
    }

    /**
     * Lista pedidos de viagem de um usuário com filtros e paginação.
     * Retorna uma paginação ordenada do mais recente para o mais antigo.
     *
     * @param int $userId
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function listForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = TravelOrder::where('user_id', $userId);

        if (! empty($filters['status'])) {
            $query->where('status', TravelOrderStatus::from($filters['status']));
        }

        if (! empty($filters['destination'])) {
            $query->where('destination', 'like', '%'.$filters['destination'].'%');
        }

        if (! empty($filters['departure_from'])) {
            $query->where('departure_date', '>=', $filters['departure_from']);
        }

        if (! empty($filters['departure_until'])) {
            $query->where('departure_date', '<=', $filters['departure_until']);
        }

        if (! empty($filters['return_from'])) {
            $query->where('return_date', '>=', $filters['return_from']);
        }

        if (! empty($filters['return_until'])) {
            $query->where('return_date', '<=', $filters['return_until']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Atualiza o status de um pedido de viagem.
     * Retorna o pedido atualizado e recarregado.
     *
     * @param TravelOrder $order
     * @param string $status
     * @return TravelOrder
     */
    public function updateStatus(TravelOrder $order, string $status): TravelOrder
    {
        $order->update(['status' => $status]);

        return $order->refresh();
    }
}
