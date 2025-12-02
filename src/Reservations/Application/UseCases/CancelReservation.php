<?php
declare(strict_types=1);

namespace App\Reservations\Application\UseCases;

use App\Reservations\Domain\Repository\ReservationRepository;
use App\Reservations\Domain\Entities\Reservation;

class CancelReservation
{
    /** @var ReservationRepository */
    protected $repository;

    public function __construct(ReservationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Cancel (mark as cancelled) a reservation and return updated entity
     * @param int $id
     * @return Reservation
     */
    public function execute(int $id): Reservation
    {
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            throw new \InvalidArgumentException('Reservation not found');
        }

        if ($existing->getStatus() === 'cancelled') {
            throw new \InvalidArgumentException('Reservation already cancelled');
        }

        // Prevent cancelling reservations that have already ended
        $now = new \DateTimeImmutable();
        $checkOut = $existing->getCheckOut()->toDateTime();
        if ($checkOut <= $now) {
            throw new \InvalidArgumentException('Cannot cancel a reservation that has already ended');
        }

        // Create updated entity with status cancelled
        $updated = new Reservation(
            $existing->getId(),
            $existing->getCheckIn(),
            $existing->getCheckOut(),
            $existing->getUserId(),
            $existing->getRoomId(),
            $existing->getTotalPrice(),
            'cancelled',
            $existing->getCreatedAt()
        );

        $persisted = $this->repository->save($updated);

        return $persisted;
    }
}

