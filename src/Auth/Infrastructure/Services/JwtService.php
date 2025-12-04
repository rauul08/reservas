<?php
declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    protected $secretKey;
    protected $algorithm = 'HS256';
    protected $expirationTime = 86400; // 24 hours

    public function __construct(string $secretKey, int $expirationTime = 86400)
    {
        $this->secretKey = $secretKey;
        $this->expirationTime = $expirationTime;
    }

    /**
     * Generate JWT token for user
     * @param int $userId
     * @param string $email
     * @param array $extraClaims
     * @return string
     */
    public function generateToken(int $userId, string $email, array $extraClaims = []): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expirationTime;

        $payload = array_merge([
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $userId,
            'email' => $email,
        ], $extraClaims);

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Decode and validate JWT token
     * @param string $token
     * @return object
     * @throws \Exception
     */
    public function decodeToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, $this->algorithm));
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Verify token and return user ID
     * @param string $token
     * @return int|null
     */
    public function getUserIdFromToken(string $token): ?int
    {
        try {
            $decoded = $this->decodeToken($token);
            return isset($decoded->sub) ? (int)$decoded->sub : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
