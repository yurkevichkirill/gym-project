<?php

declare(strict_types=1);

namespace App\Booking\Query;

use App\Booking\DTO\ResolvedBookingsRequestDTO;
use App\Booking\Repository\BookingRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class BookingsQuery
{
    private const array SORT_MAP = [
        'trainingId' => 't.id',
        'date' => 'w.date',
        'startTime' => 't.startTime',
        'durationMinutes' => 't.durationMinutes',
    ];

    public function __construct(
        private BookingRepository      $bookingRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(ResolvedBookingsRequestDTO $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto): array {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "b.$alias";
                $qb->addOrderBy($field, $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if ($dto->client) {
                $item->tag(['bookings_list_' . $dto->client->getId()]);
            } else {
                $item->tag(['bookings_list_all']);
            }

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(ResolvedBookingsRequestDTO $dto): int
    {
        return (int) $this->createQuery($dto, true)
            ->select("COUNT(b.id)")
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(ResolvedBookingsRequestDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->bookingRepo->createQueryBuilder('b');

        $qb->innerJoin('b.training', 't')
            ->innerJoin('t.trainerWorkTime', 'w')
            ->innerJoin("b.payment", 'p')
            ->innerJoin("p.trainer", "trainer");

        if (!$isCount) {
            $qb->addSelect('p', 'trainer', 't', 'w')
                ->innerJoin("trainer.trainingType", 'type')
                ->addSelect('type');
        }

        if ($dto->client) {
            $qb->andWhere('b.client = :client')
                ->setParameter('client', $dto->client);
        }

        if ($dto->trainer) {
            $qb->andWhere('trainer = :trainer')
                ->setParameter('trainer', $dto->trainer);
        }

        if ($dto->status) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $dto->status);
        }

        if ($dto->date) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $dto->date);
        }

        if ($dto->startTime) {
            $qb->andWhere('t.startTime = :startTime')
                ->setParameter('startTime', $dto->startTime);
        }

        if ($dto->durationMinutes !== null) {
            $qb->andWhere('t.durationMinutes = :durationMinutes')
                ->setParameter('durationMinutes', $dto->durationMinutes);
        }

        return $qb;
    }

    private function generateCacheKey(ResolvedBookingsRequestDTO $dto): string
    {

        $params = [
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'clientId' => $dto->client?->getId(),
            'trainerId' => $dto->trainer?->getId(),
            'status' => $dto->status,
            'date' => $dto->date?->format('Y-m-d'),
            'startTime' => $dto->startTime?->format('H:i:s'),
            'durationMinutes' => $dto->durationMinutes,
        ];

        return 'bookings_' . md5(json_encode($params));
    }
}
