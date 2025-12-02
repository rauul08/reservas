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
     * Update reservation dates. Returns the updated Reservation.
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

        // Determine which room to check: optional new room_id provided, otherwise keep existing
        $targetRoomId = $roomId ?? $existing->getRoomId();

        // Check room availability excluding current reservation id
        if ($this->repository->existsOverlappingReservation($targetRoomId, $ci->toString(), $co->toString(), $id)) {
            throw new \InvalidArgumentException('Room is occupied in the requested date range');
        }

        // Recalculate total price based on room price and number of nights
        $room = $this->rooms->findById($targetRoomId);
        if ($room === null) {
            throw new \InvalidArgumentException(sprintf('Room with id "%d" not found', $targetRoomId));
        }

        $nights = max(1, (int) $co->toDateTime()->diff($ci->toDateTime())->days);
        $pricePerNight = $room->getPrice();
        $newTotal = $pricePerNight * $nights;

        // Build updated reservation (keep other fields)
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
