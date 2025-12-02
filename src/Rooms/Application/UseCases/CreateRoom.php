<?php
declare(strict_types=1);

namespace App\Rooms\Application\UseCases;

use App\Rooms\Domain\Repository\RoomRepository;
use App\Rooms\Domain\Entities\Room;

class CreateRoom
{
    protected $repository;

    public function __construct(RoomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(array $data): Room
    {
        $room = new Room($data['id'] ?? uniqid('room_', true), $data['number']);
        $this->repository->save($room);

        return $room;
    }
}
