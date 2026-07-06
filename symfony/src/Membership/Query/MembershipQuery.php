<?php

declare(strict_types=1);

namespace App\Membership\Query;

use App\Membership\DTO\ResolvedMembershipsRequestDTO;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Membership\Repository\MembershipRepository;
use App\Request\SortParser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class MembershipQuery
{
    private const array SORT_MAP = [
        'membershipPlanId' => 'plan.id',
    ];

    public function __construct(
        private MembershipRepository $membershipRepo,
        private MembershipMapperInterface $mapper,
    )
    {}

    /**
     * @param array<string, string> $parsedSort
     * @return array{items: list<mixed>, total: int}
     */
    public function getData(ResolvedMembershipsRequestDTO $dto, array $parsedSort): array
    {
        $qb = $this->createQuery($dto);

        $totalQb = $this->createQuery($dto, true);
        $total = (int) $totalQb->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        $offset = ($dto->page - 1) * $dto->limit;

        foreach ($parsedSort as $alias => $order) {
            $field = self::SORT_MAP[$alias] ?? "m.$alias";
            $qb->addOrderBy($field, $order);
        }

        $qb->setFirstResult($offset)
            ->setMaxResults($dto->limit);

        $memberships = $qb->getQuery()->getResult();

        $items = array_map(fn ($membership) => $this->mapper->map($membership), $memberships);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    private function createQuery(ResolvedMembershipsRequestDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->membershipRepo->createQueryBuilder('m')
            ->leftJoin('m.plan', 'plan')
            ->leftJoin('m.payment', 'p');

        if (!$isCount) {
            $qb->addSelect('plan', 'p');
        }

        if ($dto->client !== null) {
            $qb->andWhere('m.client = :client')
                ->setParameter('client', $dto->client);
        }

        if ($dto->status !== null) {
            $qb->andWhere('m.status = :status')
                ->setParameter('status', $dto->status);
        }

        if ($dto->membershipPlan !== null) {
            $qb->andWhere('m.plan = :plan')
                ->setParameter('plan', $dto->membershipPlan);
        }

        if ($dto->minVisits !== null) {
            $qb->andWhere('m.visits >= :minVisits')
                ->setParameter('minVisits', $dto->minVisits);
        }

        if ($dto->maxVisits !== null) {
            $qb->andWhere('m.visits <= :maxVisits')
                ->setParameter('maxVisits', $dto->maxVisits);
        }

        return $qb;
    }

    /**
     * @return array<string, string>
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedMembershipsRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedMembershipsRequestDTO::ALLOWED_SORT_FIELDS);
    }

}
