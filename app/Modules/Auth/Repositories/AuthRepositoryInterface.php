<?php

namespace App\Modules\Auth\Repositories;

interface AuthRepositoryInterface
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?object;

    /**
     * Create a new user
     */
    public function create(array $data): object;
}

