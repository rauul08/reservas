<?php
declare(strict_types=1);

namespace App\Reservations\Domain\ValueObjects;

use DateTimeImmutable;

class CheckOutDate
{
    protected $date;

    public function __construct(string $date)
    {
        $dt = new DateTimeImmutable($date);
        $this->date = $dt;
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->date;
    }

    public function toString(): string
    {
        return $this->date->format('Y-m-d H:i:s');
    }
}
