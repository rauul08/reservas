<?php
declare(strict_types=1);

namespace App\Users\Infrastructure\Repository;

use App\Users\Domain\Repository\UserRepository;
use App\Users\Domain\Entities\User;

class MySQLUserRepository implements UserRepository
{
    /** @var \PDO */
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (id, first_name, last_name, email, phone) VALUES (:id, :first_name, :last_name, :email, :phone)'
            . ' ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), phone = VALUES(phone)');

        $stmt->execute([
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
        ]);
    }

    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (! $row) {
            return null;
        }

        return new User(
            (int) $row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'] ?? null,
            isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : new \DateTimeImmutable()
        );
    }
}
