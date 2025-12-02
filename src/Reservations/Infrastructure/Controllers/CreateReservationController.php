<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Reservations\Application\UseCases\CreateReservation;

class CreateReservationController
{
    /** @var CreateReservation */
    protected $useCase;

    public function __construct(CreateReservation $useCase)
    {
        $this->useCase = $useCase;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $raw = (string) $request->getBody();
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            $payload = json_encode(['error' => 'Malformed JSON', 'details' => $error]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!is_array($data)) {
            $payload = json_encode(['error' => 'Invalid request body']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate required fields
        $required = ['check_in', 'check_out', 'user_id', 'room_id'];
        $missing = [];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                $missing[] = $f;
            }
        }

        if (!empty($missing)) {
            $payload = json_encode(['error' => 'Missing required fields', 'fields' => $missing]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $reservation = $this->useCase->execute($data);

            $payload = json_encode(['id' => $reservation->getId()]);
            $response->getBody()->write($payload);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $payload = json_encode(['error' => 'Invalid data', 'details' => $e->getMessage()]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Throwable $e) {
            // Log error if desired via injected logger; here return generic 500
            $payload = json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
