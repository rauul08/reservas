<?php
declare(strict_types=1);

namespace App\Reservations\Application\UseCases;

use App\Reservations\Domain\Repository\ReservationRepository;
use App\Rooms\Domain\Repository\RoomRepository as RoomRepositoryInterface;
use App\Reservations\Domain\ValueObjects\CheckInDate;
use App\Reservations\Domain\ValueObjects\CheckOutDate;
use App\Reservations\Domain\Entities\Reservation;

class UpdateReservation
{
    /** @var ReservationRepository */
    protected $repository;

    /** @var RoomRepositoryInterface */
    protected $rooms;

    public function __construct(ReservationRepository $repository, RoomRepositoryInterface $rooms)
    {
        $this->repository = $repository;
        $this->rooms = $rooms;
    }

    /**
     * Actualizar fechas de reserva. Devuelve la reserva actualizada.
     * @param int $id
     * @param string $checkIn
     * @param string $checkOut
     * @return Reservation
     */
    public function execute(int $id, string $checkIn, string $checkOut, ?int $roomId = null): Reservation
    {
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            throw new \InvalidArgumentException('Reservation not found');
        }

        if ($existing->getStatus() === 'cancelled') {
            throw new \InvalidArgumentException('Cannot modify a cancelled reservation');
        }

        $ci = new CheckInDate($checkIn);
        $co = new CheckOutDate($checkOut);

        if ($co->toDateTime() <= $ci->toDateTime()) {
            throw new \InvalidArgumentException('check_out must be greater than check_in');
        }

        // Determinar qué habitación verificar: se proporciona un nuevo room_id opcional, de lo contrario, conservar el existente
        $targetRoomId = $roomId ?? $existing->getRoomId();

        // Verificar la disponibilidad de la habitación excluyendo el ID de reserva actual
        if ($this->repository->existsOverlappingReservation($targetRoomId, $ci->toString(), $co->toString(), $id)) {
            throw new \InvalidArgumentException('Room is occupied in the requested date range');
        }

        // Recalcular el precio total en función del precio de la habitación y el número de noches
        $room = $this->rooms->findById($targetRoomId);
        if ($room === null) {
            throw new \InvalidArgumentException(sprintf('Room with id "%d" not found', $targetRoomId));
        }

        $nights = max(1, (int) $co->toDateTime()->diff($ci->toDateTime())->days);
        $pricePerNight = $room->getPrice();
        $newTotal = $pricePerNight * $nights;

        // Crear reserva actualizada (mantener otros campos)
        $updated = new Reservation(
            $existing->getId(),
            $ci,
            $co,
            $existing->getUserId(),
            $targetRoomId,
            $newTotal,
            $existing->getStatus(),
            $existing->getCreatedAt()
        );

        $persisted = $this->repository->save($updated);

        return $persisted;
    }
}
