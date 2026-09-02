<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Closes M-2 from docs/lote-2/seguridad-2026-09-01.md: is_admin is the only
 * gate on User::canAccessPanel() and must never be settable through mass
 * assignment. Not exploitable today (no registration route, no
 * UserResource), but it stays armed for the day a `User::create($request->
 * validated())` shows up — this test is what catches that regression.
 */
class UserMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_is_ignored_by_fill(): void
    {
        $user = new User;
        $user->fill([
            'name' => 'Alguien',
            'email' => 'alguien@example.test',
            'password' => 'irrelevant',
            'is_admin' => true,
        ]);

        $this->assertFalse((bool) $user->is_admin);
    }

    public function test_is_admin_is_ignored_by_create(): void
    {
        $user = User::create([
            'name' => 'Alguien',
            'email' => 'alguien2@example.test',
            'password' => 'irrelevant',
            'is_admin' => true,
        ]);

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }
}
