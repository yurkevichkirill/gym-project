<?php

declare(strict_types=1);

namespace App\Training\Resolver;

use App\Client\Repository\ClientRepository;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\Training\DTO\GetTrainingsRequestDTO;
use App\Training\DTO\ResolvedTrainingsRequestDTO;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
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

final readonly class GetTrainingsResolver implements ValueResolverInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
        private SerializerInterface&DenormalizerInterface $serializer,
        private ValidatorInterface $validator,
        private Security $security,
    ) {}

    /**
     * @return iterable<int, ResolvedTrainingsRequestDTO>
     * @throws DateMalformedStringException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== ResolvedTrainingsRequestDTO::class) {
            return [];
        }

        $queryParams = $request->query->all();

        if (array_key_exists('isBusy', $queryParams)) {
            $queryParams['isBusy'] = filter_var($queryParams['isBusy'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        try {
            /** @var GetTrainingsRequestDTO $rawDto */
            $rawDto = $this->serializer->denormalize(
                $queryParams,
                GetTrainingsRequestDTO::class,
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

        $user = $this->security->getUser();
        $trainer = null;

        if ($user instanceof Trainer) {
            $trainer = $user;
        }

        elseif ($rawDto->trainerId !== null) {
            /** @var int $trainerId */
            $trainerId = $rawDto->trainerId;
            $trainer = $this->trainerRepo->find($rawDto->trainerId)
                ?? throw new NotFoundHttpException("Trainer with ID $trainerId not found");
        }

        $client = null;
        if ($rawDto->clientId !== null) {
            /** @var int $clientId */
            $clientId = $rawDto->clientId;
            $client = $this->clientRepo->find($rawDto->clientId)
                ?? throw new NotFoundHttpException("Client with ID $clientId not found");
        }

        $date = $rawDto->date !== null ? new DateTimeImmutable($rawDto->date) : null;
        $startTime = $rawDto->startTime !== null ? DateTimeImmutable::createFromFormat('H:i:s', $rawDto->startTime) : null;
        if ($startTime === false) {
            $startTime = null;
        }

        yield new ResolvedTrainingsRequestDTO(
            trainer: $trainer,
            client: $client,
            status: $rawDto->status,
            date: $date,
            startTime: $startTime,
            durationMinutes: $rawDto->durationMinutes,
            isBusy: $rawDto->isBusy,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }
}
