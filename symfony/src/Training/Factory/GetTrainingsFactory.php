<?php

declare(strict_types=1);

namespace App\Training\Factory;

use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Request\Utils\RequestParser;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\Training\DTO\GetTrainings;
use App\Training\DTO\TrainingFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetTrainingsFactory
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
        private RequestParser $parser,
    ) {}

    public function fromRequest(Request $request, ?Trainer $trainer = null, ?Client $client = null): GetTrainings
    {
        if ($client === null) {
            if ($clientId = $request->query->get('clientId')) {
                $client = $this->clientRepo->find((int)$clientId);

                if (!$client) {
                    throw new NotFoundHttpException('Client not found');
                }
            }
        }

        if ($trainer === null) {
            if ($trainerId = $request->query->get('trainerId')) {
                $trainer = $this->trainerRepo->find((int)$trainerId);

                if (!$trainer) {
                    throw new NotFoundHttpException('Trainer not found');
                }
            }
        }

        $statusInput = $request->query->get('status');
        $status = $statusInput ? BookingStatusEnum::tryFrom($statusInput) : null;

        if ($statusInput && !$status) {
            throw new BadRequestHttpException('Invalid status');
        }

        $filter = new TrainingFilter(
            trainer: $trainer,
            client: $client,
            date: $this->parser->parseDate($request->query->get('date')),
            durationMinutes: $this->parser->toInt($request->query->get('durationMinutes')),
            startTime: $this->parser->parseTime($request->query->get('startTime')),
            status: $status,
        );

        $allowedParams = ['startTime', 'durationMinutes', 'clientId', 'date', 'status', 'bookedAt'];

        return new GetTrainings(
            sort: $this->parser->parseSort($request->query->get('sort', 'bookedAt:ASC'), $allowedParams),
            filter: $filter,
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page'),
            limit: $this->parser->toPositiveInt($request->query->get('limit'), 'limit'),
        );
    }
}
