<?php

declare(strict_types=1);

namespace App\Response\SwaggerDocDTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'General object for meta-data')]
final readonly class MetaDTO
{
    /**
     * @param array<string, string> $sort
     */
    public function __construct(
        #[OA\Property] public PaginationMetaDTO $pagination,
        #[OA\Property] public array $sort,
    ) {}
}
