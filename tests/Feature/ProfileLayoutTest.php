<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_the_panel_sidebar_for_admin(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profile Information')
            ->assertSee('Academic Years') // only present in the admin sidebar
            ->assertSee('Question Bank');
    }
}
