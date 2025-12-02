<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Reservations\Application\UseCases\CancelReservation;

class CancelReservationController
{
    /** @var CancelReservation */
    protected $useCase;

    /** @var \App\Reservations\Infrastructure\Mappers\ReservationApiMapper */
    protected $apiMapper;

    public function __construct(CancelReservation $useCase, \App\Reservations\Infrastructure\Mappers\ReservationApiMapper $apiMapper)
    {
        $this->useCase = $useCase;
        $this->apiMapper = $apiMapper;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        if ($id <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid id']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $updated = $this->useCase->execute($id);
            $payload = $this->apiMapper->toArray($updated);
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => 'Invalid data', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Server error', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
