<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the is_admin gate on User::canAccessPanel(). This must NEVER be
 * relaxed to make a test pass — see docs/lote-2/00-contrato-datos.md on the
 * dev-only admin account.
 */
class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_user_is_denied_access_to_the_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_an_admin_user_can_reach_the_panel_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
