<?php
declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                // Database configuration for XAMPP MySQL. Edit credentials if needed.
                'db' => [
                    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=reservademo;charset=utf8mb4',
                    'user' => 'root',
                    'pass' => '',
                    'options' => [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES => false,
                    ],
                ],
                // Google OAuth configuration
                'google_oauth' => [
                    'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
                    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
                    'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost/reservademo/public/frontend/auth-callback.html',
                ],
                // JWT configuration
                'jwt' => [
                    'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production-' . bin2hex(random_bytes(16)),
                    'expiration' => 86400, // 24 hours
                ],
            ]);
        }
    ]);
};
