<?php

declare(strict_types=1);

namespace App\Booking\Query;

use App\Booking\DTO\ResolvedBookingsRequestDTO;
use App\Booking\Mapper\BookingMapperInterface;
use App\Booking\Repository\BookingRepository;
use App\Request\SortParser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class ClientBookingsQuery
{
    private const array SORT_MAP = [
        'trainingId' => 't.id',
        'date' => 'w.date',
        'startTime' => 't.startTime',
        'durationMinutes' => 't.durationMinutes',
    ];

    public function __construct(
        private BookingRepository $bookingRepo,
        private BookingMapperInterface $mapper,
    )
    {}

    /**
     * @param array<string, string> $parsedSort
     * @return array{items: list<mixed>, total: int}
     */
    public function getData(ResolvedBookingsRequestDTO $dto, array $parsedSort): array
    {
        $qb = $this->createQuery($dto);

        $totalQb = $this->createQuery($dto, true);
        $total = (int) $totalQb->select('COUNT(b.id)')->getQuery()->getSingleScalarResult();

        $offset = ($dto->page - 1) * $dto->limit;

        foreach ($parsedSort as $alias => $order) {
            $field = self::SORT_MAP[$alias] ?? "b.$alias";
            $qb->addOrderBy($field, $order);
        }

        $qb->setFirstResult($offset)
            ->setMaxResults($dto->limit);

        $bookings = $qb->getQuery()->getResult();

        $items = array_map(fn ($booking) => $this->mapper->map($booking), $bookings);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    private function createQuery(ResolvedBookingsRequestDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->bookingRepo->createQueryBuilder('b');

        $qb->innerJoin('b.training', 't')
            ->innerJoin('t.trainerWorkTime', 'w')
            ->innerJoin('b.payment', 'p')
            ->innerJoin('p.trainer', 'trainer')
            ->innerJoin('b.client', 'c');

        if (!$isCount) {
            $qb->addSelect('p', 'trainer', 't', 'w', 'c')
                ->innerJoin('trainer.trainingType', 'type')
                ->addSelect('type');
        }

        if ($dto->client !== null) {
            $qb->andWhere('c = :client')
                ->setParameter('client', $dto->client);
        }

        if ($dto->trainer !== null) {
            $qb->andWhere('trainer = :trainer')
                ->setParameter('trainer', $dto->trainer);
        }

        if ($dto->status !== null) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $dto->status);
        }

        if ($dto->date !== null) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $dto->date);
        }

        if ($dto->startTime !== null) {
            $qb->andWhere('t.startTime = :startTime')
                ->setParameter('startTime', $dto->startTime);
        }

        if ($dto->durationMinutes !== null) {
            $qb->andWhere('t.durationMinutes = :durationMinutes')
                ->setParameter('durationMinutes', $dto->durationMinutes);
        }

        return $qb;
    }

    /**
     * @return array<string, string>
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedBookingsRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedBookingsRequestDTO::ALLOWED_SORT_FIELDS);
    }

}
