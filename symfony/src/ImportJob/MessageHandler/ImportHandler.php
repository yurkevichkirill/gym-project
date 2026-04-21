<?php

declare(strict_types=1);

namespace App\ImportJob\MessageHandler;

use App\ImportError\Service\ImportErrorService;
use App\ImportJob\Enum\ImportResultEnum;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Repository\ImportJobRepository;
use App\ImportJob\Service\ImportService;
use Psr\Log\LoggerInterface;
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
        private LoggerInterface $clientLogger,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(ImportMessage $message): void
    {
        $context = [
            'domain' => 'client',
            'operation' => 'process_message',
            'job_id' => $message->jobId,
            'email' => $message->dto->email,
        ];

        $this->clientLogger->info('Import message processing started', $this->ctx($context, 'started'));

        $this->jobRepo->markProcessing($message->jobId);

        try {
            $violations = $this->validator->validate($message->dto, groups: ['import']);

            if (count($violations) > 0) {
                $this->clientLogger->warning('Import validation failed', $this->ctx($context, 'rejected', [
                    'violations' => (string) $violations,
                ]));

                throw new RuntimeException((string) $violations);
            }

            $result = $this->service->import($message->dto);

            match ($result) {
                ImportResultEnum::CREATED => $this->jobRepo->incrementProcessed($message->jobId),
                ImportResultEnum::SKIPPED => $this->jobRepo->incrementSkipped($message->jobId),
            };

            $this->clientLogger->info('Import message processed', $this->ctx($context, match ($result) {
                ImportResultEnum::CREATED => 'created',
                ImportResultEnum::SKIPPED => 'skipped',
            }));

        } catch (Throwable $e) {
            $this->jobRepo->incrementFailed($message->jobId);

            $job = $this->jobRepo->find($message->jobId);

            $this->errorService->create(
                $job,
                (array) $message->dto,
                $e->getMessage(),
            );

            $this->clientLogger->error('Import message failed', $this->ctx($context, 'failed', [
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]));

            throw $e;
        }

        $this->jobRepo->markFinishedIfDone($message->jobId);

        $this->clientLogger->info('Import message finished', $this->ctx($context, 'finished'));
    }

    private function ctx(array $context, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
            'domain' => 'client',
            'outcome' => $outcome,
        ];
    }
}
