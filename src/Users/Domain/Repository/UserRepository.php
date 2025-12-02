<?php
declare(strict_types=1);

namespace App\Users\Domain\Repository;

use App\Users\Domain\Entities\User;

interface UserRepository
{
    public function save(User $user): void;
    public function findById(int $id);
}
