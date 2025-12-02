<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Reservations\Domain\Repository\ReservationRepository;
use App\Reservations\Infrastructure\Mappers\ReservationApiMapper;

class ListReservationsController
{
    /** @var ReservationRepository */
    protected $repository;

    /** @var ReservationApiMapper */
    protected $apiMapper;

    public function __construct(ReservationRepository $repository, ReservationApiMapper $apiMapper)
    {
        $this->repository = $repository;
        $this->apiMapper = $apiMapper;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();

        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $perPage = isset($params['per_page']) ? max(1, min(100, (int) $params['per_page'])) : 10;
        $offset = ($page - 1) * $perPage;

        $criteria = [];
        if (!empty($params['user_id'])) {
            $criteria['user_id'] = (int) $params['user_id'];
        }
        if (!empty($params['room_id'])) {
            $criteria['room_id'] = (int) $params['room_id'];
        }
        if (!empty($params['status'])) {
            $criteria['status'] = $params['status'];
        }
        if (!empty($params['from'])) {
            $criteria['from'] = $params['from'];
        }
        if (!empty($params['to'])) {
            $criteria['to'] = $params['to'];
        }

        $reservations = $this->repository->findAll($criteria, $perPage, $offset);
        $total = $this->repository->countByCriteria($criteria);

        $data = [];
        foreach ($reservations as $r) {
            $data[] = $this->apiMapper->toArray($r);
        }

        $payload = [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ],
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
