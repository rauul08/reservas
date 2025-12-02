<?php
declare(strict_types=1);

namespace App\Reservations\Application\DTO;

use App\Reservations\Domain\Entities\Reservation;

class ReservationDTO
{
    public $id;
    public $check_in;
    public $check_out;
    public $user_id;
    public $room_id;
    public $total_price;
    public $status;
    public $created_at;

    public static function fromEntity(Reservation $r): ReservationDTO
    {
        $dto = new self();
        $dto->id = $r->getId();
        $dto->check_in = $r->getCheckIn()->toString();
        $dto->check_out = $r->getCheckOut()->toString();
        $dto->user_id = $r->getUserId();
        $dto->room_id = $r->getRoomId();
        $dto->total_price = $r->getTotalPrice();
        $dto->status = $r->getStatus();
        $dto->created_at = $r->getCreatedAt()->format('Y-m-d H:i:s');

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
