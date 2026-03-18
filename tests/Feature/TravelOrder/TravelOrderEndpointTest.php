<?php

namespace Tests\Feature\TravelOrder;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TravelOrderEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ?string $token = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function authHeaders(): array
    {
        $this->token ??= auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ──────────────────────────────────────────────
    //  POST /api/travel-orders (store)
    // ──────────────────────────────────────────────

    public function test_create_travel_order_returns_201(): void
    {
        $payload = [
            'destination' => 'Tokyo, Japão',
            'departure_date' => now()->addWeek()->format('Y-m-d'),
            'return_date' => now()->addWeeks(2)->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/travel-orders', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'destination',
                    'departure_date',
                    'return_date',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonFragment([
                'destination' => 'Tokyo, Japão',
                'status' => 'requested',
                'user_id' => $this->user->id,
            ]);

        $this->assertDatabaseHas('travel_orders', [
            'user_id' => $this->user->id,
            'destination' => 'Tokyo, Japão',
            'status' => 'requested',
        ]);
    }

    public function test_create_travel_order_validation_fails(): void
    {
        $response = $this->postJson('/api/travel-orders', [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_create_travel_order_requires_authentication(): void
    {
        $response = $this->postJson('/api/travel-orders', [
            'destination' => 'Paris, França',
            'departure_date' => now()->addWeek()->format('Y-m-d'),
            'return_date' => now()->addWeeks(2)->format('Y-m-d'),
        ]);

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────
    //  GET /api/travel-orders (index)
    // ──────────────────────────────────────────────

    public function test_list_travel_orders_returns_paginated_data(): void
    {
        TravelOrder::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/travel-orders', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'destination', 'departure_date', 'return_date', 'status'],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_list_travel_orders_filters_by_status(): void
    {
        TravelOrder::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => TravelOrderStatus::Requested,
        ]);
        TravelOrder::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/travel-orders?status=approved', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'approved']);
    }

    public function test_list_travel_orders_filters_by_destination(): void
    {
        TravelOrder::factory()->create([
            'user_id' => $this->user->id,
            'destination' => 'Tokyo, Japão',
        ]);
        TravelOrder::factory()->create([
            'user_id' => $this->user->id,
            'destination' => 'Paris, França',
        ]);

        $response = $this->getJson('/api/travel-orders?destination=Tokyo', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['destination' => 'Tokyo, Japão']);
    }

    public function test_list_travel_orders_filters_by_departure_period(): void
    {
        TravelOrder::factory()->create([
            'user_id' => $this->user->id,
            'departure_date' => '2026-06-01',
            'return_date' => '2026-06-15',
        ]);
        TravelOrder::factory()->create([
            'user_id' => $this->user->id,
            'departure_date' => '2026-08-01',
            'return_date' => '2026-08-15',
        ]);

        $response = $this->getJson(
            '/api/travel-orders?departure_from=2026-05-01&departure_until=2026-07-01',
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_only_sees_own_orders(): void
    {
        TravelOrder::factory()->count(2)->create(['user_id' => $this->user->id]);
        TravelOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/travel-orders', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ──────────────────────────────────────────────
    //  GET /api/travel-orders/{id} (show)
    // ──────────────────────────────────────────────

    public function test_show_travel_order_returns_200(): void
    {
        $order = TravelOrder::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/travel-orders/{$order->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $order->id,
                'destination' => $order->destination,
            ]);
    }

    public function test_show_travel_order_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/travel-orders/99999', $this->authHeaders());

        $response->assertStatus(404)
            ->assertJson(['message' => 'Viagem não encontrada.']);
    }

    public function test_show_travel_order_returns_403_for_other_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/travel-orders/{$order->id}", $this->authHeaders());

        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────
    //  PATCH /api/travel-orders/{id} (updateDetails)
    // ──────────────────────────────────────────────

    public function test_owner_can_update_travel_order_details_when_requested(): void
    {
        $order = TravelOrder::factory()->create(['user_id' => $this->user->id, 'status' => TravelOrderStatus::Requested]);

        $payload = [
            'destination' => 'Lisboa, Portugal',
            'departure_date' => now()->addWeek()->format('Y-m-d'),
            'return_date' => now()->addWeeks(2)->format('Y-m-d'),
        ];

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}",
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonFragment([
                'destination' => 'Lisboa, Portugal',
                'solicitante' => $this->user->name,
            ]);

        $this->assertDatabaseHas('travel_orders', [
            'id' => $order->id,
            'destination' => 'Lisboa, Portugal',
        ]);
    }

    public function test_owner_cannot_update_travel_order_details_when_approved_or_cancelled(): void
    {
        $order = TravelOrder::factory()->approved()->create(['user_id' => $this->user->id]);

        $payload = [
            'destination' => 'Lisboa, Portugal',
            'departure_date' => now()->addWeek()->format('Y-m-d'),
            'return_date' => now()->addWeeks(2)->format('Y-m-d'),
        ];

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}",
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(409)
            ->assertJsonFragment([
                'message' => 'Não foi possível alterar o pedido de viagem, pois ele já foi aprovado ou cancelado!',
            ]);
    }

    public function test_owner_cannot_update_travel_order_details_of_other_user(): void
    {
        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $otherUser->id]);

        $payload = [
            'destination' => 'Lisboa, Portugal',
            'departure_date' => now()->addWeek()->format('Y-m-d'),
            'return_date' => now()->addWeeks(2)->format('Y-m-d'),
        ];

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}",
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────
    //  PATCH /api/travel-orders/{id}/status (updateStatus)
    // ──────────────────────────────────────────────

    public function test_approve_travel_order(): void
    {
        Notification::fake();

        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $otherUser->id]);

        $manager = User::factory()->create();
        $managerToken = auth('api')->login($manager);

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}/status",
            ['status' => 'approved'],
            ['Authorization' => "Bearer $managerToken"]
        );

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'approved']);

        $this->assertDatabaseHas('travel_orders', [
            'id' => $order->id,
            'status' => 'approved',
        ]);
    }

    public function test_cancel_travel_order(): void
    {
        Notification::fake();

        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $otherUser->id]);

        $manager = User::factory()->create();
        $managerToken = auth('api')->login($manager);

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}/status",
            ['status' => 'cancelled'],
            ['Authorization' => "Bearer $managerToken"]
        );

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'cancelled']);
    }

    public function test_owner_cannot_update_status_of_own_order(): void
    {
        $order = TravelOrder::factory()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}/status",
            ['status' => 'approved'],
            $this->authHeaders()
        );

        $response->assertStatus(403);
    }

    public function test_update_status_requires_valid_status(): void
    {
        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}/status",
            ['status' => 'invalid'],
            $this->authHeaders()
        );

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_approved_order_with_past_departure(): void
    {
        Notification::fake();

        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->approved()->create([
            'user_id' => $otherUser->id,
            'departure_date' => now()->subDay(),
            'return_date' => now()->addWeek(),
        ]);

        $manager = User::factory()->create();
        $managerToken = auth('api')->login($manager);

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}/status",
            ['status' => 'cancelled'],
            ['Authorization' => "Bearer $managerToken"]
        );

        $response->assertStatus(409)
            ->assertJsonFragment([
                'message' => 'Não foi possível cancelar o pedido de viagem, pois a data de ida já passou!',
            ]);
    }

    public function test_can_cancel_approved_order_with_future_departure(): void
    {
        Notification::fake();

        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->approved()->create([
            'user_id' => $otherUser->id,
            'departure_date' => now()->addMonth(),
            'return_date' => now()->addMonths(2),
        ]);

        $manager = User::factory()->create();
        $managerToken = auth('api')->login($manager);

        $response = $this->patchJson(
            "/api/travel-orders/{$order->id}/status",
            ['status' => 'cancelled'],
            ['Authorization' => "Bearer $managerToken"]
        );

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'cancelled']);
    }

    public function test_update_status_dispatches_notification(): void
    {
        Notification::fake();

        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $otherUser->id]);

        $manager = User::factory()->create();
        $managerToken = auth('api')->login($manager);

        $this->patchJson(
            "/api/travel-orders/{$order->id}/status",
            ['status' => 'approved'],
            ['Authorization' => "Bearer $managerToken"]
        );

        Notification::assertSentTo(
            $otherUser,
            \App\Notifications\TravelOrderStatusChanged::class
        );
    }

    public function test_access_denied_to_other_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/travel-orders/{$order->id}", $this->authHeaders());

        $response->assertStatus(403)
            ->assertJson(['message' => 'Ação não autorizada.']);
    }
}
