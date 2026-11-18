<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_guests_can_not_call_logout(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_logout_button_is_shown_to_authenticated_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertSee('Cerrar sesión');
    }
}
