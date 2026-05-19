<?php

declare(strict_types=1);

namespace App\ImportJob\Entity;

use App\ImportError\Entity\ImportError;
use App\ImportJob\Enum\ImportStatusEnum;
use App\ImportJob\Repository\ImportJobRepository;
use App\ImportJobItem\Entity\ImportJobItem;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportJobRepository::class)]
class ImportJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(
        targetEntity: ImportError::class,
        mappedBy: 'job',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $errors;

    #[ORM\OneToMany(
        targetEntity: ImportJobItem::class,
        mappedBy: 'job',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $items;

    #[ORM\Column(type: Types::ENUM, length: 50)]
    private ImportStatusEnum $status;

    #[ORM\Column]
    private int $total;

    #[ORM\Column]
    private int $processed;

    #[ORM\Column]
    private int $failed;

    #[ORM\Column]
    private int $skipped;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    public function __construct(int $total)
    {
        $this->total = $total;
        $this->processed = 0;
        $this->failed = 0;
        $this->skipped = 0;
        $this->status = ImportStatusEnum::PENDING;
        $this->createdAt = new DateTimeImmutable();

        $this->errors = new ArrayCollection();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ImportStatusEnum
    {
        return $this->status;
    }

    public function setStatus(ImportStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function setProcessed(int $processed): static
    {
        $this->processed = $processed;

        return $this;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function setFailed(int $failed): static
    {
        $this->failed = $failed;

        return $this;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function setSkipped(int $skipped): static
    {
        $this->skipped = $skipped;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getErrors(): Collection
    {
        return $this->errors;
    }

    public function addError(ImportError $error): static
    {
        if (!$this->errors->contains($error)) {
            $this->errors->add($error);
        }

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ImportJobItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }

        return $this;
    }

    public function incrementFailed(): void
    {
        $this->failed++;
    }

    public function markProcessing(): void
    {
        if ($this->status === ImportStatusEnum::PENDING) {
            $this->status = ImportStatusEnum::PROCESSING;
        }
    }

    public function markFinished(): void
    {
        $this->finishedAt = new DateTimeImmutable();

        $this->status = $this->failed > 0
            ? ImportStatusEnum::FAILED
            : ImportStatusEnum::DONE;
    }
}
