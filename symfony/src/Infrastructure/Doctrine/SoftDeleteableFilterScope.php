<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

final readonly class SoftDeleteableFilterScope
{
    private const string FILTER_NAME = 'softdeleteable';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed
    {
        $filters = $this->entityManager->getFilters();
        $wasEnabled = $filters->isEnabled(self::FILTER_NAME);

        if (!$wasEnabled) {
            return $operation();
        }

        $filters->disable(self::FILTER_NAME);

        try {
            return $operation();
        } finally {
            $filters->enable(self::FILTER_NAME);
        }
    }
}
