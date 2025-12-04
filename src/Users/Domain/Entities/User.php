<?php
declare(strict_types=1);

namespace App\Users\Domain\Entities;

use DateTimeImmutable;

class User
{
    protected $id;
    protected $firstName;
    protected $lastName;
    protected $email;
    protected $phone;
    protected $createdAt;
    protected $googleId;
    protected $pictureUrl;

    public function __construct(
        int $id, 
        string $firstName, 
        string $lastName, 
        string $email, 
        ?string $phone, 
        DateTimeImmutable $createdAt,
        ?string $googleId = null,
        ?string $pictureUrl = null
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->createdAt = $createdAt;
        $this->googleId = $googleId;
        $this->pictureUrl = $pictureUrl;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function getPictureUrl(): ?string
    {
        return $this->pictureUrl;
    }
}
