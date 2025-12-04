<?php
declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

class GoogleOAuthService
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;

        $this->client = new \Google_Client();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->addScope('email');
        $this->client->addScope('profile');
        
        // Force account selection on every login
        $this->client->setPrompt('select_account');
    }

    /**
     * Get the authorization URL to redirect user to Google
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access token and get user profile
     * @param string $code
     * @return array ['email' => string, 'name' => string, 'google_id' => string, 'picture' => string|null]
     * @throws \Exception
     */
    public function getUserProfile(string $code): array
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new \Exception('Error fetching access token: ' . ($token['error_description'] ?? $token['error']));
            }

            $this->client->setAccessToken($token);

            // Get user info from Google
            $oauth2 = new \Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();

            return [
                'email' => $userInfo->getEmail(),
                'name' => $userInfo->getName(),
                'google_id' => $userInfo->getId(),
                'picture' => $userInfo->getPicture(),
                'given_name' => $userInfo->getGivenName(),
                'family_name' => $userInfo->getFamilyName(),
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to get user profile from Google: ' . $e->getMessage());
        }
    }
}
