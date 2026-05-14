<?php

declare(strict_types=1);

namespace App\Booking\Resolver;

use App\Booking\DTO\GetBookingsRequestDTO;
use App\Booking\DTO\ResolvedBookingsRequestDTO;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
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

final readonly class GetBookingsResolver implements ValueResolverInterface
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
        if ($argument->getType() !== ResolvedBookingsRequestDTO::class) {
            return [];
        }

        /** @var GetBookingsRequestDTO $rawDto */
        $rawDto = $this->serializer->denormalize(
            $request->query->all(),
            GetBookingsRequestDTO::class,
            null,
            [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        );

        $errors = $this->validator->validate($rawDto);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

        $user = $this->security->getUser();
        $client = null;

        if ($user instanceof Client) {
            $client = $user;
        }

        elseif ($rawDto->clientId) {
            $client = $this->clientRepo->find($rawDto->clientId)
                ?? throw new NotFoundHttpException("Client with ID $rawDto->clientId not found");
        }

        $trainer = null;
        if ($rawDto->trainerId) {
            $trainer = $this->trainerRepo->find($rawDto->trainerId)
                ?? throw new NotFoundHttpException("Trainer with ID $rawDto->trainerId not found");
        }

        yield new ResolvedBookingsRequestDTO(
            trainer: $trainer,
            client: $client,
            status: $rawDto->status,
            date: $rawDto->date ? new DateTimeImmutable($rawDto->date) : null,
            startTime: $rawDto->startTime ? DateTimeImmutable::createFromFormat('H:i:s', $rawDto->startTime) : null,
            durationMinutes: $rawDto->durationMinutes,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }
}
