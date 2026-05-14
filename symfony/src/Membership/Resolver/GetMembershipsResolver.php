<?php

declare(strict_types=1);

namespace App\Membership\Resolver;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Membership\DTO\GetMembershipsRequestDTO;
use App\Membership\DTO\ResolvedMembershipsRequestDTO;
use App\MembershipPlan\Repository\MembershipPlanRepository;
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

final readonly class GetMembershipsResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private ClientRepository $clientRepo,
        private MembershipPlanRepository $membershipPlanRepo,
        private Security $security,
    )
    {}

    /**
     * @throws BadRequestException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== ResolvedMembershipsRequestDTO::class) {
            return [];
        }

        try {
            /** @var GetMembershipsRequestDTO $rawDto */
            $rawDto = $this->serializer->denormalize(
                $request->query->all(),
                GetMembershipsRequestDTO::class,
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

        if ($user instanceof Client) {
            $client = $user;
        }

        elseif ($rawDto->clientId) {
            $client = $this->clientRepo->find($rawDto->clientId)
                ?? throw new NotFoundHttpException("Client with ID $rawDto->clientId not found");
        }

        $membershipPlan = null;
        if ($rawDto->membershipPlanId) {
            $membershipPlan = $this->membershipPlanRepo->find($rawDto->membershipPlanId)
                ?? throw new NotFoundHttpException("Membership plan with ID $rawDto->membershipPlanId not found");
        }

        yield new ResolvedMembershipsRequestDTO(
            membershipPlan: $membershipPlan,
            client: $client,
            status: $rawDto->status,
            minVisits: $rawDto->minVisits,
            maxVisits: $rawDto->maxVisits,
            sort: $rawDto->sort,
            page: $rawDto->page,
            limit: $rawDto->limit,
        );
    }
}
