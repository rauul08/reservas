<?php
declare(strict_types=1);

namespace App\Reservations\Infrastructure\Mappers;

use App\Reservations\Domain\Entities\Reservation;
use App\Reservations\Domain\ValueObjects\CheckInDate;
use App\Reservations\Domain\ValueObjects\CheckOutDate;

class ReservationMapper
{
    public function toRow(Reservation $r): array
    {
        return [
            'id' => $r->getId(),
            'user_id' => $r->getUserId(),
            'room_id' => $r->getRoomId(),
            'check_in' => $r->getCheckIn()->toString(),
            'check_out' => $r->getCheckOut()->toString(),
            'total_price' => $r->getTotalPrice(),
            'status' => $r->getStatus(),
        ];
    }

    public function fromRow(array $row): Reservation
    {
        return new Reservation(
            (int) $row['id'],
            new CheckInDate($row['check_in']),
            new CheckOutDate($row['check_out']),
            (int) $row['user_id'],
            (int) $row['room_id'],
            isset($row['total_price']) ? (float) $row['total_price'] : null,
            $row['status'] ?? 'pending',
            isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : new \DateTimeImmutable()
        );
    }
}
