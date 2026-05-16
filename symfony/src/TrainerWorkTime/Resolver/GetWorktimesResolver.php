<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Resolver;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\DTO\GetWorktimesRequestDTO;
use App\TrainerWorkTime\DTO\ResolvedWorktimesRequestDTO;
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

final readonly class GetWorktimesResolver implements ValueResolverInterface
{
    public function __construct(
        private TrainerRepository   $trainerRepo,
        private SerializerInterface $serializer,
        private ValidatorInterface  $validator,
        private Security $security,
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws BadRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== ResolvedWorktimesRequestDTO::class) {
            return [];
        }

        try {
            /** @var GetWorktimesRequestDTO $rawDto */
            $rawDto = $this->serializer->denormalize(
                $request->query->all(),
                GetWorktimesRequestDTO::class,
                null,
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );
        } catch (Throwable $e) {
            throw new BadRequestHttpException('Invalid data types in query parameters.', $e);
        }

        $errors = $this->validator->validate($rawDto);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string)$errors);
        }

        $user = $this->security->getUser();
        $trainer = null;

        if ($user instanceof Trainer) {
            $trainer = $user;
        }
        elseif ($rawDto->trainerId) {
            $trainer = $this->trainerRepo->find($rawDto->trainerId)
                ?? throw new NotFoundHttpException("Trainer with ID $rawDto->trainerId not found");
        }

        yield new ResolvedWorktimesRequestDTO(
            date: $rawDto->date ? new DateTimeImmutable($rawDto->date) : null,
            trainer: $trainer,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }
}
