<?php

declare(strict_types=1);

namespace App\ImportJobItem\Service;

use App\ImportError\Entity\ImportError;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Repository\ImportJobRepository;
use App\ImportJobItem\Entity\ImportJobItem;
use App\ImportJobItem\Enum\ImportJobItemStatusEnum;
use App\ImportJobItem\Repository\ImportJobItemRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ImportJobItemManager
{
    public function __construct(
        private ImportJobRepository $jobRepo,
        private ImportJobItemRepository $jobItemRepo,
        private EntityManagerInterface $em,
    )
    {}

    public function create(ImportMessage $message): ?ImportJobItem
    {
        $job = $this->jobRepo->find($message->jobId);
        if ($job === null) {
            return null;
        }

        $existing = $this->jobItemRepo->findOneBy([
            'job' => $job,
            'rowId' => $message->rowIndex,
        ]);

        if ($existing !== null) {
            return null;
        }

        $jobItem = new ImportJobItem();
        $jobItem->setJob($job);
        $jobItem->setRowId($message->rowIndex);
        $jobItem->setStatus(ImportJobItemStatusEnum::PROCESSING);

        $this->em->persist($jobItem);

        return $jobItem;
    }

    public function fail(ImportJobItem $jobItem, ImportError $jobError): ImportJobItem
    {
        $jobItem->setStatus(ImportJobItemStatusEnum::FAILED);
        $jobItem->setError($jobError);
        $jobError->setItem($jobItem);

        $jobItem->setProcessedAt(new DateTimeImmutable());

        return $jobItem;
    }

    public function success(ImportJobItem $jobItem): ImportJobItem
    {
        $jobItem->setStatus(ImportJobItemStatusEnum::SUCCEEDED);

        $jobItem->setProcessedAt(new DateTimeImmutable());

        return $jobItem;
    }

    public function skip(ImportJobItem $jobItem): ImportJobItem
    {
        $jobItem->setStatus(ImportJobItemStatusEnum::SKIPPED);

        $jobItem->setProcessedAt(new DateTimeImmutable());

        return $jobItem;
    }
}
