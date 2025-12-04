<?php
declare(strict_types=1);

namespace App\Reservations\Domain\Repository;

use App\Reservations\Domain\Entities\Reservation;

interface ReservationRepository
{
    /**
     * Persistir en una reserva
     * @param Reservation $reservation
     */
    public function save(Reservation $reservation): Reservation;

    /**
     * Encontrar por id o devolver nula
     * @param int $id
     * @return Reservation|null
     */
    public function findById(int $id);

    /**
     * Reservas de devolución que coincidan con los criterios de paginación
     * @param array|null $criteria Claves admitidas: user_id, room_id, status, from, to
     * @param int $limit
     * @param int $offset
     * @return Reservation[]
     */
    public function findAll(?array $criteria = null, int $limit = 10, int $offset = 0): array;

    /**
     * Devolver el recuento total de criterios coincidentes (para paginación)
     * @param array|null $criteria
     * @return int
     */
    public function countByCriteria(?array $criteria = null): int;

    /**
     * Comprueba si una habitación tiene alguna reserva que se superponga al rango indicado.
     * Excluye un ID de reserva opcional (útil al actualizar una reserva existente).
     * @param int $roomId
     * @param string $checkIn
     * @param string $checkOut
     * @param int|null $excludeId
     * @return bool
     */
    public function existsOverlappingReservation(int $roomId, string $checkIn, string $checkOut, ?int $excludeId = null): bool;

    /**
     * Cancelar reserva por id
     * @param int $id
     */
    public function cancel(int $id): void;
}
