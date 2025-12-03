<?php
declare(strict_types=1);

namespace App\Rooms\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Rooms\Application\UseCases\CreateRoom;

class CreateRoomController
{
    protected $useCase;

    public function __construct(CreateRoom $useCase)
    {
        $this->useCase = $useCase;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $data = (array) $request->getParsedBody();
        // Fallback: if body parser didn't populate parsedBody (e.g. missing middleware), try raw JSON
        if (empty($data)) {
            $raw = (string) $request->getBody();
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }

        // Validate required fields
        $required = ['room_number', 'category', 'subtype', 'capacity', 'price'];
        $missing = [];
        foreach ($required as $k) {
            if (!array_key_exists($k, $data) || $data[$k] === null || $data[$k] === '') {
                $missing[] = $k;
            }
        }
        if (!empty($missing)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields: ' . implode(',', $missing)]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $room = $this->useCase->execute($data);
            $payload = [
                'id' => $room->getId(),
                'number' => $room->getRoomNumber(),
                'category' => $room->getCategory(),
                'subtype' => $room->getSubtype(),
                'capacity' => $room->getCapacity(),
                'price' => $room->getPrice(),
                'description' => $room->getDescription(),
                'status' => $room->getStatus(),
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
