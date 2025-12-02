<?php
declare(strict_types=1);

namespace App\Rooms\Infrastructure\Repository;

use App\Rooms\Domain\Repository\RoomRepository;
use App\Rooms\Domain\Entities\Room;

class MySQLRoomRepository implements RoomRepository
{
    /** @var \PDO */
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Room $room): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO rooms (id, room_number, room_type, capacity, price, description, status) VALUES (:id, :room_number, :room_type, :capacity, :price, :description, :status)'
            . ' ON DUPLICATE KEY UPDATE room_number = VALUES(room_number), room_type = VALUES(room_type), capacity = VALUES(capacity), price = VALUES(price), description = VALUES(description), status = VALUES(status)');

        $stmt->execute([
            'id' => $room->getId(),
            'room_number' => $room->getRoomNumber(),
            'room_type' => $room->getRoomType(),
            'capacity' => $room->getCapacity(),
            'price' => $room->getPrice(),
            'description' => $room->getDescription(),
            'status' => $room->getStatus(),
        ]);
    }

    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (! $row) {
            return null;
        }

        return new Room(
            (int) $row['id'],
            $row['room_number'],
            $row['room_type'],
            (int) $row['capacity'],
            (float) $row['price'],
            $row['description'] ?? null,
            $row['status'] ?? 'available'
        );
    }
}
