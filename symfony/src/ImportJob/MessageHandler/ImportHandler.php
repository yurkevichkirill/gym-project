<?php

declare(strict_types=1);

namespace App\ImportJob\MessageHandler;

use App\ImportError\Service\ImportErrorService;
use App\ImportJob\Enum\ImportResultEnum;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Repository\ImportJobRepository;
use App\ImportJob\Service\ImportService;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

#[AsMessageHandler]
final readonly class ImportHandler
{
    public function __construct(
        private ImportService $service,
        private ValidatorInterface $validator,
        private ImportErrorService $errorService,
        private ImportJobRepository $jobRepo,
    ) {}

    public function __invoke(ImportMessage $message): void
    {
        $this->jobRepo->markProcessing($message->jobId);

        try {
            $violations = $this->validator->validate($message->dto, groups: ['import']);

            if (count($violations) > 0) {
                throw new RuntimeException((string) $violations);
            }

            $result = $this->service->import($message->dto);

            match ($result) {
                ImportResultEnum::CREATED => $this->jobRepo->incrementProcessed($message->jobId),
                ImportResultEnum::SKIPPED => $this->jobRepo->incrementSkipped($message->jobId),
            };

        } catch (Throwable $e) {
            $this->jobRepo->incrementFailed($message->jobId);

            $job = $this->jobRepo->find($message->jobId);

            $this->errorService->create(
                $job,
                (array) $message->dto,
                $e->getMessage(),
            );
        }

        $this->jobRepo->markFinishedIfDone($message->jobId);
    }
}
