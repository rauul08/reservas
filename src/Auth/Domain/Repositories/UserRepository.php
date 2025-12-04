<?php
declare(strict_types=1);

namespace App\Auth\Domain\Repositories;

use App\Users\Domain\Entities\User;

interface UserRepository
{
    /**
     * Find user by email
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by Google ID
     * @param string $googleId
     * @return User|null
     */
    public function findByGoogleId(string $googleId): ?User;

    /**
     * Create new user
     * @param User $user
     * @return User
     */
    public function create(User $user): User;

    /**
     * Update existing user
     * @param User $user
     * @return void
     */
    public function update(User $user): void;
}
