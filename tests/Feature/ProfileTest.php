<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/cuenta')->assertOk()->assertSee($user->email);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/cuenta')->assertRedirect(route('login'));
    }

    public function test_personal_data_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/cuenta', [
            'name' => 'Marta Puig',
            'email' => 'nuevo@example.com',
            'phone' => '+34 600 111 222',
            'address' => 'Carrer de Sants 12, Barcelona',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $user->refresh();
        $this->assertSame('Marta Puig', $user->name);
        $this->assertSame('Carrer de Sants 12, Barcelona', $user->address);
        $this->assertNull($user->email_verified_at, 'Changing the email must reset verification.');
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/cuenta', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_can_be_changed_with_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/cuenta/password', [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-123', $user->refresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/cuenta/password', [
            'current_password' => 'nope',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasErrorsIn('password', 'current_password');
    }
}
