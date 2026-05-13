<?php

declare(strict_types=1);

namespace App\Response\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "General collection container")]
final readonly class AbstractCollectionResponseDTO
{
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(type: 'object'))]
        public array $data,

        #[OA\Property]
        public MetaDTO $meta,
    ) {}
}
