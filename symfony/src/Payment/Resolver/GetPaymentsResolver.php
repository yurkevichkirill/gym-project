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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final readonly class GetPaymentsResolver implements ValueResolverInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private Security $security,
    ) {}

    /**
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

        try {
            /** @var GetPaymentsRequestDTO $rawDto */
            $rawDto = $this->serializer->denormalize(
                $request->query->all(),
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
        elseif ($rawDto->clientId) {
            $client = $this->clientRepo->find($rawDto->clientId)
                ?? throw new NotFoundHttpException("Client with ID $rawDto->clientId not found");
        }

        if ($user instanceof Trainer) {
            $trainer = $user;
        }
        elseif ($rawDto->trainerId) {
            $trainer = $this->trainerRepo->find($rawDto->trainerId)
                ?? throw new NotFoundHttpException("Trainer with ID $rawDto->trainerId not found");
        }

        yield new ResolvedPaymentsRequestDTO(
            trainer: $trainer,
            client: $client,
            minAmount: $rawDto->minAmount,
            maxAmount: $rawDto->maxAmount,
            isRefund: $rawDto->isRefund,
            status: $rawDto->status,
            minCreatedAt: $rawDto->minCreatedAt ? new DateTimeImmutable($rawDto->minCreatedAt) : null,
            maxCreatedAt: $rawDto->maxCreatedAt ? new DateTimeImmutable($rawDto->maxCreatedAt) : null,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }
}
