<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Mappers;

use App\Reservations\Domain\Entities\Reservation;
use App\Reservations\Application\DTO\ReservationDTO;
use App\Users\Domain\Repository\UserRepository as UserRepositoryInterface;
use App\Rooms\Domain\Repository\RoomRepository as RoomRepositoryInterface;

class ReservationApiMapper
{
    /** @var UserRepositoryInterface */
    protected $users;

    /** @var RoomRepositoryInterface */
    protected $rooms;

    public function __construct(UserRepositoryInterface $users, RoomRepositoryInterface $rooms)
    {
        $this->users = $users;
        $this->rooms = $rooms;
    }

    /**
     * Convertir entidad de reserva en ReservationDTO
     * @param Reservation $r
     * @return ReservationDTO
     */
    public function toDTO(Reservation $r): ReservationDTO
    {
        return ReservationDTO::fromEntity($r);
    }

    /**
     * Convierte la entidad de reserva directamente en una matriz API que incluye usuarios y habitaciones anidados
     * @param Reservation $r
     * @return array
     */
    public function toArray(Reservation $r): array
    {
        // Datos básicos de reserva
        $base = $this->toDTO($r)->toArray();

        // Cargar entidades de usuario y sala
        $userId = $r->getUserId();
        $roomId = $r->getRoomId();

        $user = $this->users->findById($userId);
        $room = $this->rooms->findById($roomId);

        $userData = null;
        if ($user !== null) {
            $userData = [
                'id' => $user->getId(),
                'full_name' => trim($user->getFirstName() . ' ' . $user->getLastName()),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
            ];
        }

        $roomData = null;
        if ($room !== null) {
            $roomData = [
                'id' => $room->getId(),
                'number' => $room->getRoomNumber(),
                'type' => $room->getRoomType(),
                'description' => $room->getDescription(),
                'price_per_night' => $room->getPrice(),
            ];
        }

        // Construir la estructura final según el ejemplo: incluir sólo los campos solicitados
        $result = [
            'id' => $base['id'],
            'status' => $base['status'],
            'check_in' => $base['check_in'],
            'check_out' => $base['check_out'],
            'total_price' => $base['total_price'],
            'user' => $userData,
            'room' => $roomData,
        ];

        return $result;
    }
}
