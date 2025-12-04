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
     * Cancelar (marcar como cancelada) una reserva y devolver la entidad actualizada
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

        // Evitar la cancelación de reservas que ya han finalizado
        $now = new \DateTimeImmutable();
        $checkOut = $existing->getCheckOut()->toDateTime();
        if ($checkOut <= $now) {
            throw new \InvalidArgumentException('Cannot cancel a reservation that has already ended');
        }

        // Crear entidad actualizada con estado cancelado
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

