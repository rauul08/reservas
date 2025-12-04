<?php
declare(strict_types=1);

namespace App\Rooms\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Rooms\Domain\Repository\RoomRepository;
use App\Rooms\Domain\Entities\Room;

class UpdateRoomController
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
        $data = (array) $request->getParsedBody();
        if (empty($data)) {
            $raw = (string) $request->getBody();
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }
        // merge values: keep existing when not provided
        $roomNumber = $data['room_number'] ?? $existing->getRoomNumber();
        $category = $data['category'] ?? $existing->getCategory();
        $subtype = array_key_exists('subtype', $data) ? $data['subtype'] : $existing->getSubtype();
        $title = array_key_exists('title', $data) ? $data['title'] : $existing->getTitle();
        $rating = array_key_exists('rating', $data) ? (float)$data['rating'] : $existing->getRating();
        $amenities = $existing->getAmenities();
        if (array_key_exists('amenities', $data)) {
            if (is_string($data['amenities'])) {
                $decoded = json_decode($data['amenities'], true);
                $amenities = is_array($decoded) ? $decoded : array_map('trim', explode(',', $data['amenities']));
            } elseif (is_array($data['amenities'])) {
                $amenities = $data['amenities'];
            }
        }
        $imageUrl = array_key_exists('image_url', $data) ? $data['image_url'] : $existing->getImageUrl();
        $destination = array_key_exists('destination', $data) ? $data['destination'] : $existing->getDestination();
        $capacity = array_key_exists('capacity', $data) ? (int)$data['capacity'] : $existing->getCapacity();
        $price = array_key_exists('price', $data) ? (float)$data['price'] : $existing->getPrice();
        $description = array_key_exists('description', $data) ? $data['description'] : $existing->getDescription();
        $status = $data['status'] ?? $existing->getStatus();

        $room = new Room($existing->getId(), $roomNumber, $category, $subtype, $title, $rating, $amenities, $imageUrl, $destination, $capacity, $price, $description, $status);
        $this->repository->save($room);

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
