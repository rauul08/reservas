<?php
declare(strict_types=1);

namespace App\Rooms\Domain\Repository;

use App\Rooms\Domain\Entities\Room;

interface RoomRepository
{
    public function save(Room $room): void;
    public function findById(int $id);
    public function findAll(array $criteria = [], int $limit = 100, int $offset = 0): array;
    public function countByCriteria(array $criteria = []): int;
}
