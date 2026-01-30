<?php

namespace App\Payment\Entity;

use App\Client\Entity\Client;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('public-payment')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('public-payment')]
    #[Assert\NotBlank]
    private ?Client $client = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups('public-payment')]
    #[Assert\NotBlank]
    #[Assert\GreaterThanOrEqual(0)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::ENUM)]
    #[Groups('public-payment')]
    #[Assert\NotBlank]
    private ?PaymentCategoryEnum $category = null;

    #[ORM\Column(type: Types::ENUM, options: ['default' => PaymentStatusEnum::PENDING])]
    #[Groups('public-payment')]
    private ?PaymentStatusEnum $status = null;

    #[ORM\Column(nullable: true, options: ["default" => "CURRENT_TIMESTAMP"])]
    #[Groups('public-payment')]
    private ?\DateTimeImmutable $paid_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCategory(): ?PaymentCategoryEnum
    {
        return $this->category;
    }

    public function setCategory(PaymentCategoryEnum $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getStatus(): ?PaymentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PaymentStatusEnum $status): static
    {
        if($this->status === PaymentStatusEnum::PENDING && $status === PaymentStatusEnum::PAID) {
            $this->paid_at = new DateTimeImmutable();
        }
        $this->status = $status;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paid_at;
    }

    public function setPaidAt(\DateTimeImmutable $paid_at): static
    {
        $this->paid_at = $paid_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeDefaults(): void
    {
        $this->status = PaymentStatusEnum::PENDING;
    }
}
