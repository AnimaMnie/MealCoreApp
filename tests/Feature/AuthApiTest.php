<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testy integracyjne REST API: authentication (Sanctum).
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_always_gets_user_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'jan',
            'email' => 'jan@example.com',
            'password' => 'Haslo1234',
            'password_confirmation' => 'Haslo1234',
            'role' => 'admin', // próba nadania sobie admina musi zostać zignorowana
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'jan@example.com')
            ->assertJsonPath('user.role', User::ROLE_USER)
            ->assertJsonStructure(['token', 'token_type']);

        $this->assertDatabaseHas('users', ['email' => 'jan@example.com', 'role' => User::ROLE_USER]);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'ala@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'ala@example.com', 'password' => 'zle-haslo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_returns_token_that_gives_access_to_api(): void
    {
        User::factory()->create(['email' => 'ala@example.com']); // hasło z fabryki: "password"

        $token = $this->postJson('/api/auth/login', ['email' => 'ala@example.com', 'password' => 'password'])
            ->assertOk()
            ->json('token');

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'ala@example.com');
    }

    public function test_protected_endpoints_require_token(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->getJson('/api/meals')->assertUnauthorized();
    }
}
