<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListTravelOrdersRequest;
use App\Http\Requests\StoreTravelOrderRequest;
use App\Http\Requests\UpdateTravelOrderStatusRequest;
use App\Http\Requests\UpdateTravelOrderRequest;
use App\Http\Resources\TravelOrderResource;
use App\Models\TravelOrder;
use App\Services\TravelOrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TravelOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TravelOrderService $service
    ) {}

    /**
     * Lista pedidos de viagem do usuário autenticado com filtros opcionais.
     * Retorna 200 OK com coleção paginada de pedidos.
     * Retorna 401 se o token for inválido/ausente.
     * Retorna 422 se houver erro de validação nos filtros.
     * Retorna 500 se houver erro interno.
     *
     * @param ListTravelOrdersRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(ListTravelOrdersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TravelOrder::class);

        $orders = $this->service->listOrders($request->user(), $request->filters());

        return TravelOrderResource::collection($orders);
    }

    /**
     * Cria um novo pedido de viagem para o usuário autenticado.
     * Retorna 201 Created com o pedido criado.
     * Retorna 401 se o token for inválido/ausente.
     * Retorna 422 se houver erro de validação.
     * Retorna 500 se houver erro interno.
     *
     * @param StoreTravelOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        $order = $this->service->createOrder($request->validated(), $request->user());

        return (new TravelOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe um pedido de viagem pelo ID.
     * Retorna 200 OK com os dados do pedido.
     * Retorna 401 se o token for inválido/ausente.
     * Retorna 403 se o pedido pertencer a outro usuário.
     * Retorna 404 se o pedido não for encontrado.
     * Retorna 500 se houver erro interno.
     *
     * @param TravelOrder $travelOrder
     * @return TravelOrderResource
     */
    public function show(TravelOrder $travelOrder): TravelOrderResource
    {
        $this->authorize('view', $travelOrder);

        return new TravelOrderResource($travelOrder);
    }

    /**
     * Atualiza o status de um pedido de viagem (approved/cancelled).
     * Retorna 200 OK com o pedido atualizado.
     * Retorna 401 se o token for inválido/ausente.
     * Retorna 403 se o usuário for o dono do pedido.
     * Retorna 404 se o pedido não for encontrado.
     * Retorna 409 se a regra de negócio impedir o cancelamento.
     * Retorna 422 se houver erro de validação.
     * Retorna 500 se houver erro interno.
     *
     * @param UpdateTravelOrderStatusRequest $request
     * @param TravelOrder $travelOrder
     * @return TravelOrderResource
     */
    public function updateStatus(UpdateTravelOrderStatusRequest $request, TravelOrder $travelOrder): TravelOrderResource
    {
        $this->authorize('updateStatus', $travelOrder);

        $order = $this->service->updateStatus($travelOrder, $request->validated('status'));

        return new TravelOrderResource($order);
    }

    /**
     * Atualiza os detalhes (destino e datas) do pedido de viagem.
     *
     * A regra de negócio permite a edição apenas pelo solicitante e somente
     * quando o pedido ainda estiver no status "requested".
     *
     * @param UpdateTravelOrderRequest $request
     * @param TravelOrder $travelOrder
     * @return TravelOrderResource
     */
    public function updateDetails(UpdateTravelOrderRequest $request, TravelOrder $travelOrder): TravelOrderResource
    {
        $this->authorize('updateDetails', $travelOrder);

        $order = $this->service->updateDetails($travelOrder, $request->validated());

        return new TravelOrderResource($order);
    }
}
