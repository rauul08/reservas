<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Repository;

use App\Reservations\Domain\Repository\ReservationRepository;
use App\Reservations\Domain\Entities\Reservation;
use App\Reservations\Infrastructure\Mappers\ReservationMapper;

class MySQLReservationRepository implements ReservationRepository
{
    /** @var \PDO */
    protected $pdo;

    /** @var ReservationMapper */
    protected $mapper;

    public function __construct(\PDO $pdo, ReservationMapper $mapper)
    {
        $this->pdo = $pdo;
        $this->mapper = $mapper;
    }

    public function save(Reservation $reservation): Reservation
    {
        // Map entity to row data
        $row = $this->mapper->toRow($reservation);

        // If id is present and > 0, include it in insert/update; otherwise insert and fetch lastInsertId
        if (!empty($row['id'])) {
            $sql = 'INSERT INTO reservations (id, user_id, room_id, check_in, check_out, total_price, status) VALUES (:id, :user_id, :room_id, :check_in, :check_out, :total_price, :status)'
                 . ' ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), room_id = VALUES(room_id), check_in = VALUES(check_in), check_out = VALUES(check_out), total_price = VALUES(total_price), status = VALUES(status)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($row);

            $id = (int) $row['id'];
        } else {
            // Insert without id (auto-increment)
            $sql = 'INSERT INTO reservations (user_id, room_id, check_in, check_out, total_price, status) VALUES (:user_id, :room_id, :check_in, :check_out, :total_price, :status)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $row['user_id'],
                'room_id' => $row['room_id'],
                'check_in' => $row['check_in'],
                'check_out' => $row['check_out'],
                'total_price' => $row['total_price'],
                'status' => $row['status'],
            ]);

            $id = (int) $this->pdo->lastInsertId();
        }

        // Return the persisted entity (fetch from DB to include created_at)
        $persisted = $this->findById($id);
        if ($persisted === null) {
            throw new \RuntimeException('Failed to fetch reservation after save');
        }

        return $persisted;
    }

    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reservations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $row) {
            return null;
        }

        return $this->mapper->fromRow($row);
    }

    public function cancel(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM reservations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM reservations ORDER BY id ASC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $list = [];
        foreach ($rows as $row) {
            $list[] = $this->mapper->fromRow($row);
        }

        return $list;
    }

    public function existsOverlappingReservation(int $roomId, string $checkIn, string $checkOut, ?int $excludeId = null): bool
    {
        // Overlap condition: NOT (existing.check_out <= new.check_in OR existing.check_in >= new.check_out)
        $sql = 'SELECT COUNT(*) as cnt FROM reservations WHERE room_id = :room_id AND NOT (check_out <= :check_in OR check_in >= :check_out)';
        $params = [
            'room_id' => $roomId,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ((int) ($row['cnt'] ?? 0)) > 0;
    }
}
