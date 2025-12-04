<?php
declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Domain\Repositories\UserRepository;
use App\Users\Domain\Entities\User;

class MySQLUserRepository implements UserRepository
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToUser($row);
    }

    public function findByGoogleId(string $googleId): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE google_id = :google_id LIMIT 1');
        $stmt->execute(['google_id' => $googleId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToUser($row);
    }

    public function create(User $user): User
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, phone, google_id, picture_url, created_at) 
             VALUES (:first_name, :last_name, :email, :phone, :google_id, :picture_url, NOW())'
        );

        $stmt->execute([
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'google_id' => $user->getGoogleId(),
            'picture_url' => $user->getPictureUrl(),
        ]);

        $id = (int)$this->pdo->lastInsertId();

        // Return user with ID
        return new User(
            $id,
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getPhone(),
            new \DateTimeImmutable(),
            $user->getGoogleId(),
            $user->getPictureUrl()
        );
    }

    public function update(User $user): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users 
             SET first_name = :first_name, last_name = :last_name, email = :email, 
                 phone = :phone, google_id = :google_id, picture_url = :picture_url
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'google_id' => $user->getGoogleId(),
            'picture_url' => $user->getPictureUrl(),
        ]);
    }

    protected function mapRowToUser(array $row): User
    {
        return new User(
            (int)$row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'] ?? null,
            isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : new \DateTimeImmutable(),
            $row['google_id'] ?? null,
            $row['picture_url'] ?? null
        );
    }
}
