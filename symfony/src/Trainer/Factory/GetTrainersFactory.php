<?php

declare(strict_types=1);

namespace App\Trainer\Factory;

use App\Request\Utils\RequestParser;
use App\Trainer\DTO\GetTrainers;
use App\Trainer\DTO\TrainerFilter;
use App\TrainingType\Repository\TrainingTypeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetTrainersFactory
{
    public function __construct(
        private RequestParser $parser,
        private TrainingTypeRepository $trainingTypeRepo,
    ) {}

    public function fromRequest(Request $request): GetTrainers
    {
        $trainingType = null;

        if ($id = $request->query->get('trainingTypeId')) {
            $trainingType = $this->trainingTypeRepo->find((int) $id);

            if (!$trainingType) {
                throw new NotFoundHttpException('Training type not found');
            }
        }

        $filter = new TrainerFilter(
            minPricePerHour: $this->parser->toInt($request->query->get('minPricePerHour')),
            maxPricePerHour: $this->parser->toInt($request->query->get('maxPricePerHour')),
            trainingType: $trainingType,
        );

        $allowedSort = ['pricePerHour', 'firstName', 'lastName', 'trainingTypeId'];

        return new GetTrainers(
            sort: $this->parser->parseSort($request->query->get('sort', 'lastName:ASC'), $allowedSort),
            filter: $filter,
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page'),
            limit: $this->parser->toPositiveInt($request->query->get('limit'), 'limit'),
        );
    }
}
