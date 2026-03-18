<?php

namespace App\Repositories\Contracts;

use App\Models\TravelOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TravelOrderRepositoryInterface
{
    /**
     * Cria um novo pedido de viagem.
     * Retorna o pedido persistido no banco.
     *
     * @param array<string, mixed> $data
     * @return TravelOrder
     */
    public function create(array $data): TravelOrder;

    /**
     * Busca um pedido de viagem pelo ID.
     * Retorna o pedido encontrado ou null se não existir.
     *
     * @param int $id
     * @return TravelOrder|null
     */
    public function findById(int $id): ?TravelOrder;

    /**
     * Lista pedidos de viagem de um usuário com filtros e paginação.
     * Retorna uma paginação ordenada do mais recente para o mais antigo.
     *
     * Filtros suportados:
     * - status: string (requested|approved|cancelled)
     * - destination: string (busca parcial)
     * - departure_from/departure_until: string|date
     * - return_from/return_until: string|date
     * - per_page: int
     *
     * @param int $userId
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function listForUser(int $userId, array $filters = []): LengthAwarePaginator;

    /**
     * Atualiza o status de um pedido de viagem.
     * Retorna o pedido atualizado e recarregado.
     *
     * @param TravelOrder $order
     * @param string $status
     * @return TravelOrder
     */
    public function updateStatus(TravelOrder $order, string $status): TravelOrder;

    /**
     * Atualiza os detalhes do pedido (destino e datas).
     *
     * @param TravelOrder $order
     * @param array<string, mixed> $data
     * @return TravelOrder
     */
    public function updateDetails(TravelOrder $order, array $data): TravelOrder;
}
