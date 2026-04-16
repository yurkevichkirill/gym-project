<?php

declare(strict_types=1);

namespace App\ImportError\Service;

use App\ImportError\Entity\ImportError;
use App\ImportJob\Entity\ImportJob;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ImportErrorService
{
    public function __construct(
        private EntityManagerInterface $em,
    )
    {}

    public function create(ImportJob $job, array $payload, string $errorMessage): ImportError
    {
        $error = new ImportError(
            $job,
            $payload,
            $errorMessage,
        );

        $this->em->persist($error);
        $this->em->flush();

        return $error;
    }
}
