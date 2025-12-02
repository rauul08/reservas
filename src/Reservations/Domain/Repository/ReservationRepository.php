<?php
declare(strict_types=1);

namespace App\Reservations\Domain\Repository;

use App\Reservations\Domain\Entities\Reservation;

interface ReservationRepository
{
    /**
     * Persist a reservation
     * @param Reservation $reservation
     */
    public function save(Reservation $reservation): Reservation;

    /**
     * Find by id or return null
     * @param int $id
     * @return Reservation|null
     */
    public function findById(int $id);

    /**
     * Return all reservations
     * @return Reservation[]
     */
    public function findAll(): array;

    /**
     * Check if a room has any reservation overlapping the given range.
     * Excludes an optional reservation id (useful when updating an existing reservation).
     * @param int $roomId
     * @param string $checkIn
     * @param string $checkOut
     * @param int|null $excludeId
     * @return bool
     */
    public function existsOverlappingReservation(int $roomId, string $checkIn, string $checkOut, ?int $excludeId = null): bool;

    /**
     * Cancel reservation by id
     * @param int $id
     */
    public function cancel(int $id): void;
}
