<?php

namespace App\ImportError\Entity;

use App\ImportError\Repository\ImportErrorRepository;
use App\ImportJob\Entity\ImportJob;
use App\ImportJobItem\Entity\ImportJobItem;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportErrorRepository::class)]
class ImportError
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImportJob::class, inversedBy: 'errors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ImportJob $job;

    #[ORM\OneToOne(
        inversedBy: 'error'
    )]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ImportJobItem $item;

    #[ORM\Column(type: Types::JSON)]
    private array $payload;

    #[ORM\Column(type: Types::TEXT)]
    private string $errorMessage;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(
        ImportJob $job,
        array $payload,
        string $errorMessage
    ) {
        $this->job = $job;
        $this->payload = $payload;
        $this->errorMessage = $errorMessage;
        $this->createdAt = new DateTimeImmutable();
    }

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

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

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

    public function getItem(): ImportJobItem
    {
        return $this->item;
    }

    public function setItem(ImportJobItem $item): static
    {
        $this->item = $item;

        return $this;
    }
}
