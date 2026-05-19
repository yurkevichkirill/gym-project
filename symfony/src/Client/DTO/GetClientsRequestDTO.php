<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetClientsRequestDTO
{
    public const array ALLOWED_SORT_FIELDS = [
        'firstName', 'lastName', 'balance', 'age', 'createdAt', 'updatedAt', 'deletedAt'
    ];

    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $minAge = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $maxAge = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $minBalance = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $maxBalance = null,

        public ?bool $isDeleted = null,

        public string $sort = 'age:ASC',

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        public int $limit = 20,
    )
    {}
}
