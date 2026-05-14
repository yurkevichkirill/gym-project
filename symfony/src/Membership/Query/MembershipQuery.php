<?php

declare(strict_types=1);

namespace App\Membership\Query;

use App\Membership\DTO\ResolvedMembershipsRequestDTO;
use App\Membership\Repository\MembershipRepository;
use App\Request\SortParser;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class MembershipQuery
{
    private const array SORT_MAP = [
        'membershipPlanId' => 'p.id',
    ];

    public function __construct(
        private MembershipRepository $membershipRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(ResolvedMembershipsRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto, $parsedSort): array {

            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($parsedSort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "m.$alias";
                $qb->addOrderBy($field, $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if ($dto->client) {
                $item->tag(["memberships_list_" . $dto->client->getId()]);
            } else {
                $item->tag(["memberships_list_all"]);
            }

            return $qb->getQuery()->getResult();
        });
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getTotal(ResolvedMembershipsRequestDTO $dto): int
    {
        return (int) $this->createQuery($dto)
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(ResolvedMembershipsRequestDTO $dto): QueryBuilder
    {
        $qb = $this->membershipRepo->createQueryBuilder('m')
            ->leftJoin('m.plan', 'plan')
            ->addSelect('plan')
            ->leftJoin('m.payment', 'p')
            ->addSelect('p');

        if ($dto->client) {
            $qb->andWhere('m.client = :client')
                ->setParameter('client', $dto->client);
        }

        if ($dto->status !== null) {
            $qb->andWhere('m.status = :status')
                ->setParameter('status', $dto->status);
        }

        if ($dto->membershipPlan) {
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
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedMembershipsRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedMembershipsRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(ResolvedMembershipsRequestDTO $dto): string
    {
        $params = [
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'clientId' => $dto->client?->getId(),
            'membershipPlanId' => $dto->membershipPlan?->getId(),
            'status' => $dto->status,
            'minVisits' => $dto->minVisits,
            'maxVisits' => $dto->maxVisits,
        ];

        return 'memberships_' . md5(json_encode($params));
    }
}
