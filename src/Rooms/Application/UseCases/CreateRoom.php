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
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $roomNumber = $data['room_number'] ?? $data['number'] ?? '';
        $category = $data['category'] ?? ($data['room_type'] ?? 'single');
        $subtype = $data['subtype'] ?? null;
        $destination = $data['destination'] ?? null;
        $title = $data['title'] ?? ($data['name'] ?? null);
        $rating = isset($data['rating']) ? (float)$data['rating'] : null;
        $amenities = null;
        if (isset($data['amenities'])) {
            if (is_string($data['amenities'])) {
                $decoded = json_decode($data['amenities'], true);
                $amenities = is_array($decoded) ? $decoded : array_map('trim', explode(',', $data['amenities']));
            } elseif (is_array($data['amenities'])) {
                $amenities = $data['amenities'];
            }
        }
        $imageUrl = $data['image_url'] ?? null;
        $capacity = isset($data['capacity']) ? (int)$data['capacity'] : 1;
        $price = isset($data['price']) ? (float)$data['price'] : 0.0;
        $description = $data['description'] ?? null;
        $status = $data['status'] ?? 'available';

        $room = new Room($id, $roomNumber, $category, $subtype, $title, $rating, $amenities, $imageUrl, $destination, $capacity, $price, $description, $status);
        $this->repository->save($room);

        return $room;
    }
}
