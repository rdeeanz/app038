<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?object
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create a new user
     */
    public function create(array $data): object
    {
        return User::create($data);
    }
}

