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
        $reservations = $this->repository->findAll();

        $payload = [];
        foreach ($reservations as $r) {
            $payload[] = $this->apiMapper->toArray($r);
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
