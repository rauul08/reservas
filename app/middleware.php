<?php
declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use Slim\App;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

return function (App $app) {
    // CORS middleware: handles preflight and appends CORS headers to all responses.
    $app->add(function (Request $request, RequestHandler $handler): Response {
        $origin = $request->getHeaderLine('Origin');
        $allowedHeaders = 'X-Requested-With, Content-Type, Accept, Origin, Authorization';
        $allowedMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            // Preflight request - return empty 200 with CORS headers
            $resp = new SlimResponse(200);
        } else {
            $resp = $handler->handle($request);
        }

        // If Origin header present, echo it (allows credentials). Otherwise fall back to localhost for dev.
        if ($origin) {
            $resp = $resp->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        } else {
            $resp = $resp->withHeader('Access-Control-Allow-Origin', 'http://localhost');
        }

        $resp = $resp->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
            ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache');

        return $resp;
    });

    // Session middleware (app-specific)
    $app->add(SessionMiddleware::class);
};
