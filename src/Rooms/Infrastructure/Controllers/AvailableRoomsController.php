<?php
declare(strict_types=1);

namespace App\Rooms\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Rooms\Domain\Repository\RoomRepository as RoomRepositoryInterface;
use App\Reservations\Domain\Repository\ReservationRepository as ReservationRepositoryInterface;

class AvailableRoomsController
{
    protected $rooms;
    protected $reservations;

    public function __construct(RoomRepositoryInterface $rooms, ReservationRepositoryInterface $reservations)
    {
        $this->rooms = $rooms;
        $this->reservations = $reservations;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['per_page']) ? max(1, min(100, (int)$params['per_page'])) : 20;

        $criteria = [];
        if (!empty($params['category'])) $criteria['category'] = $params['category'];
        if (!empty($params['subtype'])) $criteria['subtype'] = $params['subtype'];
        if (!empty($params['destination'])) $criteria['destination'] = $params['destination'];
        // only rooms marked available
        $criteria['status'] = 'available';

        $guests = isset($params['guests']) ? max(1, (int)$params['guests']) : 1;
        $from = $params['from'] ?? null;
        $to = $params['to'] ?? null;

        // Validate date params if provided
        if (($from !== null && $to === null) || ($from === null && $to !== null)) {
            $response->getBody()->write(json_encode(['error' => 'Both `from` and `to` are required to check availability']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if ($from !== null && $to !== null) {
            $d1 = \DateTime::createFromFormat('Y-m-d', $from);
            $d2 = \DateTime::createFromFormat('Y-m-d', $to);
            if (!$d1 || !$d2) {
                $response->getBody()->write(json_encode(['error' => 'Invalid date format, expected YYYY-MM-DD']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            if ($d1 > $d2) {
                $response->getBody()->write(json_encode(['error' => '`from` must be before `to`']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        // Retrieve candidate rooms (server-side filter by destination/category/status)
        $candidates = $this->rooms->findAll($criteria, 1000, 0);

        $available = [];
        foreach ($candidates as $r) {
            // capacity check
            if ($r->getCapacity() < $guests) {
                continue;
            }

            // availability check when dates provided
            if ($from !== null && $to !== null) {
                if ($this->reservations->existsOverlappingReservation($r->getId(), $from, $to)) {
                    continue;
                }
            }

            $available[] = $r;
        }

        $total = count($available);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($available, $offset, $perPage);

        $data = [];
        foreach ($slice as $r) {
            $data[] = [
                'id' => $r->getId(),
                'number' => $r->getRoomNumber(),
                'category' => $r->getCategory(),
                'subtype' => $r->getSubtype(),
                'destination' => $r->getDestination(),
                'title' => $r->getTitle(),
                'rating' => $r->getRating(),
                'amenities' => $r->getAmenities(),
                'image_url' => $r->getImageUrl(),
                'capacity' => $r->getCapacity(),
                'price' => $r->getPrice(),
                'description' => $r->getDescription(),
                'status' => $r->getStatus(),
            ];
        }

        $payload = ['data' => $data, 'meta' => ['total' => $total, 'page' => $page, 'per_page' => $perPage]];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
