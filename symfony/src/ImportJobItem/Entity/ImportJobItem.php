<?php

declare(strict_types=1);

namespace App\ImportJobItem\Entity;

use App\ImportError\Entity\ImportError;
use App\ImportJob\Entity\ImportJob;
use App\ImportJobItem\Enum\ImportJobItemStatusEnum;
use App\ImportJobItem\Repository\ImportJobItemRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\UniqueConstraint(name: 'unique_item_import_job', columns: ['row_id', 'job_id'])]
#[ORM\Entity(repositoryClass: ImportJobItemRepository::class)]
final class ImportJobItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImportJob::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ImportJob $job;

    #[ORM\OneToOne(
        targetEntity: ImportError::class,
        mappedBy: 'item',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?ImportError $error = null;

    #[ORM\Column]
    private int $rowId;

    #[ORM\Column(type: Types::ENUM, length: 50)]
    private ImportJobItemStatusEnum $status;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $processedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJob(): ImportJob
    {
        return $this->job;
    }

    public function setJob(ImportJob $job): static
    {
        $this->job = $job;

        return $this;
    }

    public function getRowId(): int
    {
        return $this->rowId;
    }

    public function setRowId(int $rowId): static
    {
        $this->rowId = $rowId;

        return $this;
    }

    public function getStatus(): ImportJobItemStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ImportJobItemStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getError(): ?ImportError
    {
        return $this->error;
    }

    public function setError(?ImportError $error): void
    {
        $this->error = $error;
    }

    public function getProcessedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?DateTimeImmutable $processedAt): void
    {
        $this->processedAt = $processedAt;
    }
}
