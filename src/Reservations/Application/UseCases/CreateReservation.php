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
        // Validar que existan entidades referenciadas y analizar fechas
        $userId = (int) $data['user_id'];
        $roomId = (int) $data['room_id'];

        if ($this->users->findById($userId) === null) {
            throw new \InvalidArgumentException(sprintf('User with id "%d" not found', $userId));
        }

        $room = $this->rooms->findById($roomId);
        if ($room === null) {
            throw new \InvalidArgumentException(sprintf('Room with id "%d" not found', $roomId));
        }

        $ci = new CheckInDate($data['check_in']);
        $co = new CheckOutDate($data['check_out']);

        if ($co->toDateTime() <= $ci->toDateTime()) {
            throw new \InvalidArgumentException('check_out must be greater than check_in');
        }

        // Consulte la disponibilidad de habitaciones para el rango solicitado
        if ($this->repository->existsOverlappingReservation($roomId, $ci->toString(), $co->toString(), null)) {
            throw new \InvalidArgumentException('Room is occupied in the requested date range');
        }

        // Calcular el precio total según el precio de la habitación y el número de noches
        $nights = max(1, (int) $co->toDateTime()->diff($ci->toDateTime())->days);
        $pricePerNight = $room->getPrice();
        $totalPrice = $pricePerNight * $nights;

        // Crea una nueva entidad de reserva con el identificador de marcador de posición (0) y el precio calculado.
       // repository.save conservará y devolverá la reserva conservada.
        $reservation = new Reservation(
            0,
            $ci,
            $co,
            $userId,
            $roomId,
            $totalPrice,
            $data['status'] ?? 'pending',
            new \DateTimeImmutable()
        );

        $persisted = $this->repository->save($reservation);

        return $persisted;
    }
}
