<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication: rejestracja, logowanie i wylogowanie przez tokeny Sanctum.
 */
class AuthService
{
    /**
     * Nowe konto zawsze dostaje rolę "user" - admina nadaje tylko inny admin.
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // hashowane przez cast 'hashed' w modelu
        ]);
        $user->role = User::ROLE_USER;
        $user->save();

        return ['user' => $user->refresh(), 'token' => $this->issueToken($user)];
    }

    /**
     * @return array{user: User, token: string}
     *
     * @throws ValidationException gdy e-mail lub hasło są błędne
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return ['user' => $user, 'token' => $this->issueToken($user)];
    }

    /**
     * Usuwa tylko token, którym wykonano request (wylogowanie z jednego urządzenia).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    private function issueToken(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }
}
