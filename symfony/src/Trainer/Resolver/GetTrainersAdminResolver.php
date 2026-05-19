<?php

declare(strict_types=1);

namespace App\Trainer\Resolver;

use App\Trainer\DTO\GetTrainersRequestAdminDTO;
use App\Trainer\DTO\ResolvedTrainersRequestAdminDTO;
use App\TrainingType\Repository\TrainingTypeRepository;
use DateMalformedStringException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final readonly class GetTrainersAdminResolver implements ValueResolverInterface
{
    public function __construct(
        private TrainingTypeRepository $trainingTypeRepo,
        private SerializerInterface&DenormalizerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    /**
     * @return iterable<int, ResolvedTrainersRequestAdminDTO>
     * @throws DateMalformedStringException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== ResolvedTrainersRequestAdminDTO::class) {
            return [];
        }

        $queryParams = $request->query->all();

        if (array_key_exists('isDeleted', $queryParams)) {
            $queryParams['isDeleted'] = filter_var($queryParams['isDeleted'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if (array_key_exists('isBlocked', $queryParams)) {
            $queryParams['isBlocked'] = filter_var($queryParams['isBlocked'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        try {
            /** @var GetTrainersRequestAdminDTO $rawDto */
            $rawDto = $this->serializer->denormalize(
                $queryParams,
                GetTrainersRequestAdminDTO::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );
        } catch (Throwable $e) {
            throw new BadRequestHttpException('Invalid data types in query parameters.', $e);
        }

        $errors = $this->validator->validate($rawDto);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

        $trainingType = null;
        if ($rawDto->trainingTypeId !== null) {
            $trainingTypeId = $rawDto->trainingTypeId;
            $trainingType = $this->trainingTypeRepo->find($trainingTypeId)
                ?? throw new NotFoundHttpException("Training type with ID $trainingTypeId not found");
        }

        yield new ResolvedTrainersRequestAdminDTO(
            minPricePerHour: $rawDto->minPricePerHour,
            maxPricePerHour: $rawDto->maxPricePerHour,
            trainingType: $trainingType,
            minBalance: $rawDto->minBalance,
            maxBalance: $rawDto->maxBalance,
            isDeleted: $rawDto->isDeleted,
            isBlocked: $rawDto->isBlocked,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }
}
