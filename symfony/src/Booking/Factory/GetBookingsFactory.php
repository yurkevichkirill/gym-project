<?php

declare(strict_types=1);

namespace App\Booking\Factory;

use App\Booking\DTO\BookingFilter;
use App\Booking\DTO\GetBookings;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Request\Utils\RequestParser;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetBookingsFactory
{
    public function __construct(
        private TrainerRepository $trainerRepo,
        private ClientRepository $clientRepo,
        private RequestParser $parser,
    ) {}

    public function fromRequest(Request $request, ?Client $client = null, ?Trainer $trainer = null): GetBookings
    {
        if ($trainer === null) {
            if ($trainerId = $request->query->get('trainerId')) {
                $trainer = $this->trainerRepo->find((int)$trainerId);

                if (!$trainer) {
                    throw new NotFoundHttpException('Trainer not found');
                }
            }
        }

        if ($client === null) {
            if ($clientId = $request->query->get('clientId')) {
                $client = $this->clientRepo->find((int) $clientId);

                if (!$client) {
                    throw new NotFoundHttpException('Client not found');
                }
            }
        }

        $statusInput = $request->query->get('status');
        $status = $statusInput ? BookingStatusEnum::tryFrom($statusInput) : null;

        if ($statusInput && !$status) {
            throw new BadRequestHttpException('Invalid status');
        }

        $date = $this->parser->parseDate($request->query->get('date'));
        $startTime = $this->parser->parseTime($request->query->get('startTime'));

        $durationMinutes = $this->parser->toInt($request->query->get('durationMinutes'));

        $filter = new BookingFilter(
            client: $client,
            trainer: $trainer,
            status: $status,
            date: $date,
            startTime: $startTime,
            durationMinutes: $durationMinutes,
        );

        $allowedSortParams = ['bookedAt', 'status', 'trainingId', 'date', 'startTime', 'durationMinutes'];

        return new GetBookings(
            sort: $this->parser->parseSort($request->query->get('sort', 'bookedAt:ASC'),$allowedSortParams),
            filter: $filter,
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page'),
            limit: $this->parser->toPositiveInt($request->query->get('limit'), 'limit'),
        );
    }
}
