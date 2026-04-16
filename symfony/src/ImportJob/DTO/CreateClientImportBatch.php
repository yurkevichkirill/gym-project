<?php

declare(strict_types=1);

namespace App\ImportJob\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateClientImportBatch
{
    public function __construct(
        /** @var CreateClientImport[] */
        #[Assert\NotBlank]
        #[Assert\Valid]
        public array $clients = []
    )
    {}
}
