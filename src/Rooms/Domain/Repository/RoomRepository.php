<?php
declare(strict_types=1);

namespace App\Rooms\Domain\Repository;

use App\Rooms\Domain\Entities\Room;

interface RoomRepository
{
    public function save(Room $room): void;
    public function findById(int $id);
}
