<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Jobs\SendWelcomeEmailJob;
use App\Modules\Auth\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function __construct(
        protected AuthRepositoryInterface $repository
    ) {}

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Assign default role if specified
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        // Dispatch welcome email job
        SendWelcomeEmailJob::dispatch($user)
            ->onQueue('auth');

        Log::info('User registered', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ];
    }

    /**
     * Login user
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return [
                'success' => false,
            ];
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('User logged in', [
            'user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ];
    }

    /**
     * Logout user
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();

        Log::info('User logged out', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Refresh token
     */
    public function refreshToken(User $user): string
    {
        $user->currentAccessToken()->delete();

        return $user->createToken('auth-token')->plainTextToken;
    }
}

