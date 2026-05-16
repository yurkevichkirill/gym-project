<?php

declare(strict_types=1);

namespace App\Response\SwaggerDocDTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "Pagination meta data")]
final readonly class PaginationMetaDTO
{
    public function __construct(
        #[OA\Property] public int $page,
        #[OA\Property] public int $limit,
        #[OA\Property] public int $total,
        #[OA\Property] public int $pages,
    ) {}
}
