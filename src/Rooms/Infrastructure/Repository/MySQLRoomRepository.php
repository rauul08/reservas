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
        $id = $room->getId();
        if ($id && $id > 0) {
            // Use UPDATE for existing records to ensure reliable updates
            $stmt = $this->pdo->prepare('UPDATE rooms SET room_number = :room_number, category = :category, subtype = :subtype, title = :title, rating = :rating, amenities = :amenities, image_url = :image_url, destination = :destination, capacity = :capacity, price = :price, description = :description, status = :status WHERE id = :id');

            $subtypeVal = $room->getSubtype();
            if ($subtypeVal === null) {
                $subtypeVal = '';
            }
            $stmt->execute([
                'id' => $id,
                'room_number' => $room->getRoomNumber(),
                'category' => $room->getCategory(),
                'subtype' => $subtypeVal,
                'title' => $room->getTitle(),
                'rating' => $room->getRating(),
                'amenities' => $room->getAmenities() ? json_encode($room->getAmenities()) : null,
                'image_url' => $room->getImageUrl(),
                'destination' => $room->getDestination(),
                'capacity' => $room->getCapacity(),
                'price' => $room->getPrice(),
                'description' => $room->getDescription(),
                'status' => $room->getStatus(),
            ]);
        } else {
            $subtypeVal = $room->getSubtype();
            if ($subtypeVal === null) {
                $subtypeVal = '';
            }
            $stmt = $this->pdo->prepare('INSERT INTO rooms (room_number, category, subtype, title, rating, amenities, image_url, destination, capacity, price, description, status) VALUES (:room_number, :category, :subtype, :title, :rating, :amenities, :image_url, :destination, :capacity, :price, :description, :status)');
            $stmt->execute([
                'room_number' => $room->getRoomNumber(),
                'category' => $room->getCategory(),
                'subtype' => $subtypeVal,
                'title' => $room->getTitle(),
                'rating' => $room->getRating(),
                'amenities' => $room->getAmenities() ? json_encode($room->getAmenities()) : null,
                'image_url' => $room->getImageUrl(),
                'destination' => $room->getDestination(),
                'capacity' => $room->getCapacity(),
                'price' => $room->getPrice(),
                'description' => $room->getDescription(),
                'status' => $room->getStatus(),
            ]);
        }
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
            $row['category'],
            $row['subtype'] ?? null,
            $row['title'] ?? null,
            isset($row['rating']) && $row['rating'] !== null ? (float)$row['rating'] : null,
            isset($row['amenities']) && $row['amenities'] !== null ? json_decode($row['amenities'], true) : null,
            $row['image_url'] ?? null,
            $row['destination'] ?? null,
            (int) $row['capacity'],
            (float) $row['price'],
            $row['description'] ?? null,
            $row['status'] ?? 'available'
        );
    }

    public function findAll(array $criteria = [], int $limit = 100, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($criteria['category'])) {
            $where[] = 'category = :category';
            $params['category'] = $criteria['category'];
        }
        if (!empty($criteria['destination'])) {
            $where[] = 'destination = :destination';
            $params['destination'] = $criteria['destination'];
        }
        if (!empty($criteria['subtype'])) {
            $where[] = 'subtype = :subtype';
            $params['subtype'] = $criteria['subtype'];
        }
        if (!empty($criteria['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $criteria['status'];
        }
        if (!empty($criteria['room_number'])) {
            $where[] = 'room_number = :room_number';
            $params['room_number'] = $criteria['room_number'];
        }

        $sql = 'SELECT * FROM rooms';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY id ASC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
                $result[] = new Room(
                (int) $row['id'],
                $row['room_number'],
                $row['category'],
                $row['subtype'] ?? null,
                $row['title'] ?? null,
                isset($row['rating']) && $row['rating'] !== null ? (float)$row['rating'] : null,
                isset($row['amenities']) && $row['amenities'] !== null ? json_decode($row['amenities'], true) : null,
                $row['image_url'] ?? null,
                $row['destination'] ?? null,
                (int) $row['capacity'],
                (float) $row['price'],
                $row['description'] ?? null,
                $row['status'] ?? 'available'
            );
        }

        return $result;
    }

    public function countByCriteria(array $criteria = []): int
    {
        $where = [];
        $params = [];
        if (!empty($criteria['category'])) {
            $where[] = 'category = :category';
            $params['category'] = $criteria['category'];
        }
        if (!empty($criteria['destination'])) {
            $where[] = 'destination = :destination';
            $params['destination'] = $criteria['destination'];
        }
        if (!empty($criteria['subtype'])) {
            $where[] = 'subtype = :subtype';
            $params['subtype'] = $criteria['subtype'];
        }
        if (!empty($criteria['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $criteria['status'];
        }
        if (!empty($criteria['room_number'])) {
            $where[] = 'room_number = :room_number';
            $params['room_number'] = $criteria['room_number'];
        }

        $sql = 'SELECT COUNT(*) as cnt FROM rooms';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['cnt'] : 0;
    }
}
