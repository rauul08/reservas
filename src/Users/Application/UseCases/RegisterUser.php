<?php
declare(strict_types=1);

namespace App\Users\Application\UseCases;

use App\Users\Domain\Repository\UserRepository;
use App\Users\Domain\Entities\User;

class RegisterUser
{
    protected $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(array $data): User
    {
        $user = new User($data['id'] ?? uniqid('user_', true), $data['email']);
        $this->repository->save($user);

        return $user;
    }
}
