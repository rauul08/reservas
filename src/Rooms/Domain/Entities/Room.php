<?php
declare(strict_types=1);

namespace App\Rooms\Domain\Entities;

use DateTimeImmutable;

class Room
{
    protected $id;
    protected $roomNumber;
    protected $roomType; // 'single'|'double'|'suite'
    protected $capacity;
    protected $price;
    protected $description;
    protected $status; // 'available','maintenance','unavailable'

    public function __construct(int $id, string $roomNumber, string $roomType, int $capacity, float $price, ?string $description, string $status = 'available')
    {
        $this->id = $id;
        $this->roomNumber = $roomNumber;
        $this->roomType = $roomType;
        $this->capacity = $capacity;
        $this->price = $price;
        $this->description = $description;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoomNumber(): string
    {
        return $this->roomNumber;
    }

    public function getRoomType(): string
    {
        return $this->roomType;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
