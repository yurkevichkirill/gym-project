<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Booking\Service\BookingServiceInterface;
use App\Client\Repository\ClientRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class BookingService implements BookingServiceInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private BookingRepository $bookingRepo
    )
    {}

    public function findBy(int $clientId, array $sort, ?BookingStatusEnum $status = null): array
    {
        $client = $this->clientRepo->find($clientId);
        if(is_null($client)) {
            throw new NotFoundHttpException('Client not found');
        }
        $criteria = ['client' => $client];
        if($status) {
            $criteria['status'] = $status;
        }

        return $this->bookingRepo->findBy($criteria, $sort);
    }
}
