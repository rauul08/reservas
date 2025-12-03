<?php
declare(strict_types=1);

namespace App\Rooms\Domain\Entities;

use DateTimeImmutable;

class Room
{
    protected $id;
    protected $roomNumber;
    protected $category; // 'single'|'double'|'suite'
    protected $subtype; // e.g. 'estandar','ejecutiva'
    protected $title;
    protected $rating;
    protected $amenities; // array|null
    protected $imageUrl;
    protected $destination;
    protected $capacity;
    protected $price;
    protected $description;
    protected $status; // 'available','maintenance','unavailable'
    public function __construct(int $id, string $roomNumber, string $category, ?string $subtype, ?string $title, ?float $rating, $amenities, ?string $imageUrl, ?string $destination, int $capacity, float $price, ?string $description, string $status = 'available')
    {
        $this->id = $id;
        $this->roomNumber = $roomNumber;
        $this->category = $category;
        $this->subtype = $subtype;
        $this->title = $title;
        $this->rating = $rating;
        $this->amenities = $amenities;
        $this->imageUrl = $imageUrl;
        $this->destination = $destination;
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

    public function getCategory(): string
    {
        return $this->category;
    }

    // Backwards-compatible alias
    public function getRoomType(): string
    {
        return $this->getCategory();
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function getAmenities()
    {
        return $this->amenities;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
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
