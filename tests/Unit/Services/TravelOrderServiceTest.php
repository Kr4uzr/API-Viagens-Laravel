<?php

namespace Tests\Unit\Services;

use App\Enums\TravelOrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderStatusChanged;
use App\Repositories\Contracts\TravelOrderRepositoryInterface;
use App\Services\TravelOrderService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TravelOrderServiceTest extends TestCase
{
    private MockInterface $repository;

    private TravelOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TravelOrderRepositoryInterface::class);
        $this->service = new TravelOrderService($this->repository);
    }

    public function test_create_order_calls_repository_with_correct_data(): void
    {
        $user = new User();
        $user->id = 1;

        $inputData = [
            'destination' => 'Belo Horizonte, Brasil',
            'departure_date' => '2026-05-01',
            'return_date' => '2026-05-10',
        ];

        $expectedData = [
            ...$inputData,
            'user_id' => 1,
            'status' => TravelOrderStatus::Requested,
        ];

        $fakeOrder = new TravelOrder($expectedData);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn($fakeOrder);

        $result = $this->service->createOrder($inputData, $user);

        $this->assertSame($fakeOrder, $result);
    }

    public function test_update_status_dispatches_notification(): void
    {
        Notification::fake();

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(TravelOrderStatusChanged::class));

        $order = Mockery::mock(TravelOrder::class)->makePartial();
        $order->id = 10;
        $order->status = TravelOrderStatus::Requested;
        $order->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn($user);

        $updatedOrder = Mockery::mock(TravelOrder::class)->makePartial();
        $updatedOrder->id = 10;
        $updatedOrder->status = TravelOrderStatus::Approved;
        $updatedOrder->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn($user);

        $this->repository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($order, 'approved')
            ->andReturn($updatedOrder);

        $result = $this->service->updateStatus($order, 'approved');

        $this->assertSame($updatedOrder, $result);
    }

    public function test_cannot_cancel_approved_order_with_past_departure_date(): void
    {
        $order = new TravelOrder();
        $order->status = TravelOrderStatus::Approved;
        $order->departure_date = Carbon::yesterday();

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Não foi possível cancelar o pedido de viagem, pois a data de ida já passou!');

        $this->service->updateStatus($order, 'cancelled');
    }

    public function test_can_cancel_approved_order_with_future_departure_date(): void
    {
        Notification::fake();

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('notify')->once();

        $order = Mockery::mock(TravelOrder::class)->makePartial();
        $order->id = 5;
        $order->status = TravelOrderStatus::Approved;
        $order->departure_date = Carbon::tomorrow();
        $order->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn($user);

        $updatedOrder = Mockery::mock(TravelOrder::class)->makePartial();
        $updatedOrder->id = 5;
        $updatedOrder->status = TravelOrderStatus::Cancelled;
        $updatedOrder->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn($user);

        $this->repository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($order, 'cancelled')
            ->andReturn($updatedOrder);

        $result = $this->service->updateStatus($order, 'cancelled');

        $this->assertEquals(TravelOrderStatus::Cancelled, $result->status);
    }

    public function test_get_order_delegates_to_repository(): void
    {
        $order = new TravelOrder();
        $order->id = 7;

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(7)
            ->andReturn($order);

        $result = $this->service->getOrder(7);

        $this->assertSame($order, $result);
    }

    public function test_list_orders_delegates_to_repository(): void
    {
        $user = new User();
        $user->id = 1;

        $filters = ['status' => 'approved'];
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->repository
            ->shouldReceive('listForUser')
            ->once()
            ->with(1, $filters)
            ->andReturn($paginator);

        $result = $this->service->listOrders($user, $filters);

        $this->assertSame($paginator, $result);
    }
}
