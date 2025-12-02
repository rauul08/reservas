<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Reservations\Application\UseCases\UpdateReservation;
use App\Reservations\Infrastructure\Mappers\ReservationApiMapper;

class UpdateReservationController
{
    /** @var UpdateReservation */
    protected $useCase;

    /** @var ReservationApiMapper */
    protected $apiMapper;

    public function __construct(UpdateReservation $useCase, ReservationApiMapper $apiMapper)
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

        $raw = (string) $request->getBody();
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response->getBody()->write(json_encode(['error' => 'Malformed JSON', 'details' => json_last_error_msg()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!is_array($data)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid request body']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $required = ['check_in', 'check_out'];
        $missing = [];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                $missing[] = $f;
            }
        }

        if (!empty($missing)) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields', 'fields' => $missing]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $roomId = isset($data['room_id']) ? (int) $data['room_id'] : null;

            $updated = $this->useCase->execute($id, $data['check_in'], $data['check_out'], $roomId);

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
