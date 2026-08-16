<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student', 'parent'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Admin Dashboard');
    }

    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        $this->actingAs($this->userWithRole('teacher'))
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_generic_dashboard_route_redirects_by_role(): void
    {
        $this->actingAs($this->userWithRole('student'))
            ->get('/dashboard')
            ->assertRedirect(route('student.dashboard'));

        $this->actingAs($this->userWithRole('parent'))
            ->get('/dashboard')
            ->assertRedirect(route('parent.dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }
}
