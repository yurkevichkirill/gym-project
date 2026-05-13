<?php

declare(strict_types=1);

namespace App\Response\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "General object for meta-data")]
final readonly class MetaDTO
{
    public function __construct(
        #[OA\Property] public PaginationMetaDTO $pagination,
        #[OA\Property] public array $sort,
    ) {}
}
