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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final readonly class GetBookingsResolver implements ValueResolverInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
        private SerializerInterface&DenormalizerInterface $serializer,
        private ValidatorInterface $validator,
        private Security $security,
    ) {}

    /**
     * @return iterable<int, ResolvedBookingsRequestDTO>
     * @throws DateMalformedStringException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== ResolvedBookingsRequestDTO::class) {
            return [];
        }

        try {
            /** @var GetBookingsRequestDTO $rawDto */
            $rawDto = $this->serializer->denormalize(
                $request->query->all(),
                GetBookingsRequestDTO::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );
        } catch (Throwable $e) {
            throw new BadRequestHttpException('Invalid data types in query parameters.', $e);
        }

        $errors = $this->validator->validate($rawDto);
        if (count($errors) > 0) {
            throw new BadRequestHttpException($this->formatValidationErrors($errors));
        }

        $user = $this->security->getUser();
        $client = null;

        if ($user instanceof Client) {
            $client = $user;
        } elseif ($rawDto->clientId !== null) {
            $client = $this->clientRepo->find($rawDto->clientId)
                ?? throw new NotFoundHttpException(sprintf(
                    'Client with ID %d not found',
                    $rawDto->clientId,
                ));
        }

        $trainer = null;
        if ($rawDto->trainerId !== null) {
            $trainer = $this->trainerRepo->find($rawDto->trainerId)
                ?? throw new NotFoundHttpException(sprintf(
                    'Trainer with ID %d not found',
                    $rawDto->trainerId,
                ));
        }

        $date = $rawDto->date !== null ? new DateTimeImmutable($rawDto->date) : null;
        $startTime = $rawDto->startTime !== null
            ? DateTimeImmutable::createFromFormat('H:i:s', $rawDto->startTime)
            : null;
        if ($startTime === false) {
            $startTime = null;
        }

        yield new ResolvedBookingsRequestDTO(
            trainer: $trainer,
            client: $client,
            status: $rawDto->status,
            date: $date,
            startTime: $startTime,
            durationMinutes: $rawDto->durationMinutes,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }

    private function formatValidationErrors(ConstraintViolationListInterface $errors): string
    {
        $messages = [];

        foreach ($errors as $error) {
            $propertyPath = $error->getPropertyPath();
            $message = (string) $error->getMessage();
            $messages[] = $propertyPath === ''
                ? $message
                : sprintf('%s: %s', $propertyPath, $message);
        }

        return implode('; ', $messages);
    }
}
