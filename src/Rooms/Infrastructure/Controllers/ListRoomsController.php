<?php
declare(strict_types=1);

namespace App\Rooms\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Rooms\Domain\Repository\RoomRepository;

class ListRoomsController
{
    protected $repository;

    public function __construct(RoomRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['per_page']) ? max(1, min(100, (int)$params['per_page'])) : 20;
        $offset = ($page - 1) * $perPage;

        $criteria = [];
        if (!empty($params['category'])) $criteria['category'] = $params['category'];
        if (!empty($params['subtype'])) $criteria['subtype'] = $params['subtype'];
        if (!empty($params['status'])) $criteria['status'] = $params['status'];
        if (!empty($params['room_number'])) $criteria['room_number'] = $params['room_number'];

        $rooms = $this->repository->findAll($criteria, $perPage, $offset);
        $total = $this->repository->countByCriteria($criteria);

        $data = [];
        foreach ($rooms as $r) {
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
