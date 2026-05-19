<?php

declare(strict_types=1);

namespace App\Payment\Resolver;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Payment\DTO\GetPaymentsRequestDTO;
use App\Payment\DTO\ResolvedPaymentsRequestDTO;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
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

final readonly class GetPaymentsResolver implements ValueResolverInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
        private SerializerInterface&DenormalizerInterface $serializer,
        private ValidatorInterface $validator,
        private Security $security,
    ) {}

    /**
     * @return iterable<int, ResolvedPaymentsRequestDTO>
     * @throws DateMalformedStringException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== ResolvedPaymentsRequestDTO::class) {
            return [];
        }

        $queryParams = $request->query->all();

        if (array_key_exists('isRefund', $queryParams)) {
            $queryParams['isRefund'] = filter_var($queryParams['isRefund'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        try {
            /** @var GetPaymentsRequestDTO $rawDto */
            $rawDto = $this->serializer->denormalize(
                $queryParams,
                GetPaymentsRequestDTO::class,
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
        $client = null;
        $trainer = null;

        if ($user instanceof Client) {
            $client = $user;
        }
        elseif ($rawDto->clientId !== null) {
            $clientId = $rawDto->clientId;
            $client = $this->clientRepo->find($clientId)
                ?? throw new NotFoundHttpException("Client with ID $clientId not found");
        }

        if ($user instanceof Trainer) {
            $trainer = $user;
        }
        elseif ($rawDto->trainerId !== null) {
            $trainerId = $rawDto->trainerId;
            $trainer = $this->trainerRepo->find($trainerId)
                ?? throw new NotFoundHttpException("Trainer with ID $trainerId not found");
        }

        $minCreatedAt = $rawDto->minCreatedAt !== null ? new DateTimeImmutable($rawDto->minCreatedAt) : null;
        $maxCreatedAt = $rawDto->maxCreatedAt !== null ? new DateTimeImmutable($rawDto->maxCreatedAt) : null;

        yield new ResolvedPaymentsRequestDTO(
            trainer: $trainer,
            client: $client,
            minAmount: $rawDto->minAmount,
            maxAmount: $rawDto->maxAmount,
            isRefund: $rawDto->isRefund,
            status: $rawDto->status,
            minCreatedAt: $minCreatedAt,
            maxCreatedAt: $maxCreatedAt,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }
}
