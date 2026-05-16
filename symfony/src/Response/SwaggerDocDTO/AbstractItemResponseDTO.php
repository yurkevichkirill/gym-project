<?php

declare(strict_types=1);

namespace App\Response\SwaggerDocDTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "General item container")]
final readonly class AbstractItemResponseDTO
{
    public function __construct(
        #[OA\Property(description: "The main data object", type: "object")]
        public mixed $data,
    ) {}
}
