<?php
declare(strict_types=1);

namespace App\Rooms\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Rooms\Domain\Repository\RoomRepository;

class GetRoomController
{
    protected $repository;

    public function __construct(RoomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $room = $this->repository->findById($id);
        if ($room === null) {
            $response->getBody()->write(json_encode(['error' => 'Room not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'id' => $room->getId(),
            'number' => $room->getRoomNumber(),
            'category' => $room->getCategory(),
                'subtype' => $room->getSubtype(),
                'destination' => $room->getDestination(),
            'title' => $room->getTitle(),
            'rating' => $room->getRating(),
            'amenities' => $room->getAmenities(),
            'image_url' => $room->getImageUrl(),
            'capacity' => $room->getCapacity(),
            'price' => $room->getPrice(),
            'description' => $room->getDescription(),
            'status' => $room->getStatus(),
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
