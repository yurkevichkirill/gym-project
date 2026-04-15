<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Factory;

use App\Request\Utils\RequestParser;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\DTO\GetTrainerWorkTime;
use App\TrainerWorkTime\DTO\WorkTimeFilter;
use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetTrainerWorkTimeFactory
{
    public function __construct(
        private RequestParser $parser,
        private TrainerRepository $trainerRepo,
    )
    {}

    public function fromRequest(Request $request, ?Trainer $trainer = null): GetTrainerWorkTime
    {
        $date = $this->parser->parseDate($request->query->get('date'));

        if ($trainer === null) {
            if ($trainerId = $request->query->get('trainerId')) {
                $trainer = $this->trainerRepo->find((int)$trainerId);

                if (!$trainer) {
                    throw new NotFoundHttpException('Trainer not found');
                }
            }
        }

        $allowedSortParams = ['date', 'startTime', 'endTime'];

        return new GetTrainerWorkTime(
            sort: $this->parser->parseSort($request->query->get('sort', 'date:ASC'), $allowedSortParams),
            filter: new WorkTimeFilter(
                trainer: $trainer,
                date: $date,
            ),
            page: $this->parser->toInt($request->query->get('page')) ?? 1,
            limit: $this->parser->toInt($request->query->get('limit')) ?? 20,
        );
    }
}
