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
        $existing = $this->jobItemRepo->findOneBy([
            'job' => $this->jobRepo->find($message->jobId),
            'rowId' => $message->rowIndex,
        ]);

        if ($existing) {
            return null;
        }

        $importJob = $this->jobRepo->find($message->jobId);

        $jobItem = new ImportJobItem();
        $jobItem->setJob($importJob);
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
