<?php
declare(strict_types=1);

namespace App\Rooms\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Rooms\Domain\Repository\RoomRepository;

class DeleteRoomController
{
    protected $repository;

    public function __construct(RoomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            $response->getBody()->write(json_encode(['error' => 'Room not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // For simplicity, we'll delete by setting status to 'unavailable' if repository lacks delete.
        // If repository supports hard delete in the future, replace this with a delete call.
        $existingStatus = $existing->getStatus();
        if ($existingStatus === 'unavailable') {
            $response->getBody()->write(json_encode(['error' => 'Room already unavailable']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $existingRoom = new \App\Rooms\Domain\Entities\Room(
            $existing->getId(),
            $existing->getRoomNumber(),
            $existing->getCategory(),
            $existing->getSubtype(),
            $existing->getTitle(),
            $existing->getRating(),
            $existing->getAmenities(),
            $existing->getImageUrl(),
            $existing->getDestination(),
            $existing->getCapacity(),
            $existing->getPrice(),
            $existing->getDescription(),
            'unavailable'
        );
        $this->repository->save($existingRoom);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
