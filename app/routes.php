<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Reservations\Infrastructure\Controllers\CreateReservationController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    // Reservations endpoints (DDD-style controllers)
    $app->group('/reservations', function (Group $group) {
        // Create reservation (expects body JSON with check_in, check_out, user_id, room_id)
        $group->post('', CreateReservationController::class);
        // List all reservations
        $group->get('', \App\Reservations\Infrastructure\Controllers\ListReservationsController::class);
        // Get by id
        $group->get('/{id}', \App\Reservations\Infrastructure\Controllers\GetReservationController::class);
        // Update dates
        $group->put('/{id}', \App\Reservations\Infrastructure\Controllers\UpdateReservationController::class);
        // Cancel reservation
        $group->delete('/{id}', \App\Reservations\Infrastructure\Controllers\CancelReservationController::class);
    });

    // Rooms endpoints for frontend integration
    $app->group('/rooms', function (Group $group) {
        $group->get('/available', \App\Rooms\Infrastructure\Controllers\AvailableRoomsController::class);
        $group->get('', \App\Rooms\Infrastructure\Controllers\ListRoomsController::class);
        $group->post('', \App\Rooms\Infrastructure\Controllers\CreateRoomController::class);
        $group->get('/{id}', \App\Rooms\Infrastructure\Controllers\GetRoomController::class);
        $group->put('/{id}', \App\Rooms\Infrastructure\Controllers\UpdateRoomController::class);
        $group->delete('/{id}', \App\Rooms\Infrastructure\Controllers\DeleteRoomController::class);
    });
};
