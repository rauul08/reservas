<?php
declare(strict_types=1);

namespace App\Reservations\Application\UseCases;

use App\Reservations\Domain\Repository\ReservationRepository;
use App\Reservations\Domain\Entities\Reservation;
use App\Reservations\Domain\ValueObjects\CheckInDate;
use App\Reservations\Domain\ValueObjects\CheckOutDate;
use App\Users\Domain\Repository\UserRepository as UserRepositoryInterface;
use App\Rooms\Domain\Repository\RoomRepository as RoomRepositoryInterface;

class CreateReservation
{
    /** @var ReservationRepository */
    protected $repository;

    /** @var UserRepositoryInterface */
    protected $users;

    /** @var RoomRepositoryInterface */
    protected $rooms;

    public function __construct(ReservationRepository $repository, UserRepositoryInterface $users, RoomRepositoryInterface $rooms)
    {
        $this->repository = $repository;
        $this->users = $users;
        $this->rooms = $rooms;
    }

    /**
     * @param array $data
     * @return Reservation
     */
    public function execute(array $data): Reservation
    {
        // Validate referenced entities exist
        $userId = (int) $data['user_id'];
        $roomId = (int) $data['room_id'];

        if ($this->users->findById($userId) === null) {
            throw new \InvalidArgumentException(sprintf('User with id "%d" not found', $userId));
        }

        if ($this->rooms->findById($roomId) === null) {
            throw new \InvalidArgumentException(sprintf('Room with id "%d" not found', $roomId));
        }

        // Build a new Reservation entity with placeholder id (0) and defaults;
        // repository.save will persist and return the persisted Reservation.
        $reservation = new Reservation(
            0,
            new CheckInDate($data['check_in']),
            new CheckOutDate($data['check_out']),
            $userId,
            $roomId,
            isset($data['total_price']) ? (float) $data['total_price'] : null,
            $data['status'] ?? 'pending',
            new \DateTimeImmutable()
        );

        $persisted = $this->repository->save($reservation);

        return $persisted;
    }
}
