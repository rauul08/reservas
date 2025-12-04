<?php
declare(strict_types=1);

namespace App\Auth\Application\UseCases;

use App\Auth\Domain\Repositories\UserRepository;
use App\Auth\Infrastructure\Services\GoogleOAuthService;
use App\Auth\Infrastructure\Services\JwtService;
use App\Users\Domain\Entities\User;

class LoginWithGoogle
{
    protected $userRepository;
    protected $googleService;
    protected $jwtService;

    public function __construct(
        UserRepository $userRepository,
        GoogleOAuthService $googleService,
        JwtService $jwtService
    ) {
        $this->userRepository = $userRepository;
        $this->googleService = $googleService;
        $this->jwtService = $jwtService;
    }

    /**
     * Process Google OAuth callback: exchange code for profile, find or create user, return JWT
     * @param string $authorizationCode
     * @return array ['token' => string, 'user' => array]
     * @throws \Exception
     */
    public function execute(string $authorizationCode): array
    {
        // Get user profile from Google
        $profile = $this->googleService->getUserProfile($authorizationCode);

        // Try to find user by Google ID first
        $user = $this->userRepository->findByGoogleId($profile['google_id']);

        if (!$user) {
            // Try to find by email (user might exist without Google ID)
            $user = $this->userRepository->findByEmail($profile['email']);

            if ($user) {
                // Update existing user with Google ID
                $updatedUser = new User(
                    $user->getId(),
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getEmail(),
                    $user->getPhone(),
                    $user->getCreatedAt(),
                    $profile['google_id'],
                    $profile['picture'] ?? null
                );
                $this->userRepository->update($updatedUser);
                $user = $updatedUser;
            } else {
                // Create new user
                $newUser = new User(
                    0, // ID will be assigned by DB
                    $profile['given_name'] ?? $profile['name'],
                    $profile['family_name'] ?? '',
                    $profile['email'],
                    null, // phone
                    new \DateTimeImmutable(),
                    $profile['google_id'],
                    $profile['picture'] ?? null
                );
                $user = $this->userRepository->create($newUser);
            }
        }

        // Generate JWT token
        $token = $this->jwtService->generateToken(
            $user->getId(),
            $user->getEmail(),
            ['name' => $user->getFirstName() . ' ' . $user->getLastName()]
        );

        return [
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getFirstName() . ' ' . $user->getLastName(),
                'picture' => $user->getPictureUrl(),
            ],
        ];
    }
}
