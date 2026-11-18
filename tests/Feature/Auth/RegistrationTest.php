<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk()->assertSee('Crear cuenta de cliente');
    }

    public function test_new_customers_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Marta Puig',
            'email' => 'marta@example.com',
            'phone' => '600 123 456',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'marta@example.com', 'phone' => '600 123 456']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'marta@example.com']);

        $this->post('/register', [
            'name' => 'Marta Puig',
            'email' => 'marta@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->post('/register', [
            'name' => 'Marta Puig',
            'email' => 'marta@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }
}
