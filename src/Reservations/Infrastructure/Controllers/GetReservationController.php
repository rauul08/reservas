<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Reservations\Domain\Repository\ReservationRepository;
use App\Reservations\Infrastructure\Mappers\ReservationApiMapper;

class GetReservationController
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
        $id = isset($args['id']) ? (int) $args['id'] : 0;

        if ($id <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid id']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $reservation = $this->repository->findById($id);
        if ($reservation === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $payload = $this->apiMapper->toArray($reservation);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
