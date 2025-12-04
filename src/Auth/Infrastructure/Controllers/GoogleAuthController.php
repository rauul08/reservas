<?php
declare(strict_types=1);

namespace App\Auth\Infrastructure\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Auth\Infrastructure\Services\GoogleOAuthService;
use App\Auth\Application\UseCases\LoginWithGoogle;

class GoogleAuthController
{
    protected $googleService;
    protected $loginWithGoogle;

    public function __construct(GoogleOAuthService $googleService, LoginWithGoogle $loginWithGoogle)
    {
        $this->googleService = $googleService;
        $this->loginWithGoogle = $loginWithGoogle;
    }

    /**
     * Redirect user to Google OAuth page
     */
    public function redirectToGoogle(Request $request, Response $response): Response
    {
        $authUrl = $this->googleService->getAuthorizationUrl();
        return $response
            ->withHeader('Location', $authUrl)
            ->withStatus(302);
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleCallback(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        
        if (!isset($params['code'])) {
            $error = $params['error'] ?? 'Authorization code not found';
            $response->getBody()->write(json_encode([
                'error' => 'OAuth failed',
                'message' => $error
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->loginWithGoogle->execute($params['code']);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'token' => $result['token'],
                'user' => $result['user']
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Login failed',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
