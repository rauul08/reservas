<?php
declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use App\Reservations\Domain\Repository\ReservationRepository as ReservationRepositoryInterface;
use App\Reservations\Infrastructure\Repository\MySQLReservationRepository;
use App\Reservations\Infrastructure\Mappers\ReservationMapper;
use App\Rooms\Domain\Repository\RoomRepository as RoomRepositoryInterface;
use App\Rooms\Infrastructure\Repository\MySQLRoomRepository;
use App\Users\Domain\Repository\UserRepository as UserRepositoryInterface;
use App\Users\Infrastructure\Repository\MySQLUserRepository;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        // PDO binding: prefer Settings db.dsn if configured, otherwise sqlite fallback
        \PDO::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $db = null;
            try {
                $db = $settings->get('db');
            } catch (\Throwable $e) {
                // ignore if no db settings
            }

            if (is_array($db) && !empty($db['dsn'])) {
                $user = $db['user'] ?? null;
                $pass = $db['pass'] ?? null;
                $options = $db['options'] ?? [];
                $pdo = new \PDO($db['dsn'], $user, $pass, $options);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                return $pdo;
            }

            // Fallback to a sqlite file under project var/ (creates file if missing)
            $path = __DIR__ . '/../var/database.sqlite';
            $pdo = new \PDO('sqlite:' . $path);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $pdo;
        },

        // Reservations mapper & repository
        ReservationMapper::class => function () {
            return new ReservationMapper();
        },

        ReservationRepositoryInterface::class => function (ContainerInterface $c) {
            return new MySQLReservationRepository($c->get(\PDO::class), $c->get(ReservationMapper::class));
        },

        // Rooms repository
        RoomRepositoryInterface::class => function (ContainerInterface $c) {
            return new MySQLRoomRepository($c->get(\PDO::class));
        },

        // Users repository
        UserRepositoryInterface::class => function (ContainerInterface $c) {
            return new MySQLUserRepository($c->get(\PDO::class));
        },

        // API Mapper for reservations (includes nested user and room)
        \App\Reservations\Infrastructure\Mappers\ReservationApiMapper::class => function (ContainerInterface $c) {
            return new \App\Reservations\Infrastructure\Mappers\ReservationApiMapper(
                $c->get(UserRepositoryInterface::class),
                $c->get(RoomRepositoryInterface::class)
            );
        },

        // Use cases
        \App\Reservations\Application\UseCases\UpdateReservation::class => function (ContainerInterface $c) {
            return new \App\Reservations\Application\UseCases\UpdateReservation(
                $c->get(ReservationRepositoryInterface::class),
                $c->get(RoomRepositoryInterface::class)
            );
        },

        \App\Reservations\Application\UseCases\CancelReservation::class => function (ContainerInterface $c) {
            return new \App\Reservations\Application\UseCases\CancelReservation(
                $c->get(ReservationRepositoryInterface::class)
            );
        },
    ]);
};
