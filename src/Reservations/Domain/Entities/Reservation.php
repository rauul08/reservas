<?php
declare(strict_types=1);

namespace App\Reservations\Domain\Entities;

use App\Reservations\Domain\ValueObjects\CheckInDate;
use App\Reservations\Domain\ValueObjects\CheckOutDate;
use DateTimeImmutable;

class Reservation
{
    protected $id;
    protected $checkIn;
    protected $checkOut;
    protected $userId;
    protected $roomId;
    protected $totalPrice;
    protected $status; // 'pendiente', 'confirmada', 'cancelada'
    protected $createdAt;

    public function __construct(int $id, CheckInDate $checkIn, CheckOutDate $checkOut, int $userId, int $roomId, ?float $totalPrice, string $status, DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->checkIn = $checkIn;
        $this->checkOut = $checkOut;
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->totalPrice = $totalPrice;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCheckIn(): CheckInDate
    {
        return $this->checkIn;
    }

    public function getCheckOut(): CheckOutDate
    {
        return $this->checkOut;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRoomId(): int
    {
        return $this->roomId;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
