<?php

declare(strict_types=1);

namespace App\Response\SwaggerDocDTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "Standard error response representation")]
final readonly class ErrorResponseDTO
{
    public function __construct(
        #[OA\Property(
            description: "Human-readable error message",
            example: "An error occurred while processing your request."
        )]
        public string $message,
    ) {}
}
