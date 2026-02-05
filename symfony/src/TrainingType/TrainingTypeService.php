<?php

declare(strict_types=1);

namespace App\TrainingType;

use App\TrainingType\Repository\TrainingTypeRepository;
use App\TrainingType\TrainingTypeServiceInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class TrainingTypeService implements TrainingTypeServiceInterface
{
    public function __construct(
        private TrainingTypeRepository $trainingTypeRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function findBy($sort): array
    {
        return $this->gymCache->get('training_types', function (CacheItem $item) use ($sort): array
            {
                $item->tag(['training_types_list']);

                return $this->trainingTypeRepo->findBy([], $sort);
            }
        );
    }
}
