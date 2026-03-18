<?php

namespace Tests\Unit\Policies;

use App\Models\TravelOrder;
use App\Models\User;
use App\Policies\TravelOrderPolicy;
use Tests\TestCase;

class TravelOrderPolicyTest extends TestCase
{
    private TravelOrderPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TravelOrderPolicy();
    }

    public function test_view_any_returns_true_for_authenticated_user(): void
    {
        $user = new User();
        $user->id = 1;

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_owner_can_view_own_order(): void
    {
        $user = new User();
        $user->id = 1;

        $order = new TravelOrder();
        $order->user_id = 1;

        $this->assertTrue($this->policy->view($user, $order));
    }

    public function test_user_cannot_view_other_users_order(): void
    {
        $user = new User();
        $user->id = 1;

        $order = new TravelOrder();
        $order->user_id = 2;

        $this->assertFalse($this->policy->view($user, $order));
    }

    public function test_owner_cannot_update_status_of_own_order(): void
    {
        $user = new User();
        $user->id = 1;

        $order = new TravelOrder();
        $order->user_id = 1;

        $this->assertFalse($this->policy->updateStatus($user, $order));
    }

    public function test_other_user_can_update_status(): void
    {
        $user = new User();
        $user->id = 2;

        $order = new TravelOrder();
        $order->user_id = 1;

        $this->assertTrue($this->policy->updateStatus($user, $order));
    }
}
