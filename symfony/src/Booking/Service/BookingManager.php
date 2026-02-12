<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Training\Repository\TrainingRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingManager
{
    public function __construct(
        private BookingRepository $bookingRepo,
        private TrainingRepository $trainingRepo
    )
    {}

    public function create(Client $client, BookingRequest $dto): Booking
    {
        $training = $this->trainingRepo->find($dto->trainingId);
        if(is_null($training)) {
            throw new NotFoundHttpException("Trainer not found");
        }
        $booking = new Booking();
        $booking->setClient($client);
        $booking->setTraining($training);
        $this->bookingRepo->create($booking);

        return $booking;
    }

}
