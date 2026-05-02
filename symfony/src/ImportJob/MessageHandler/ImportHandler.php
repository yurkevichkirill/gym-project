<?php

declare(strict_types=1);

namespace App\ImportJob\MessageHandler;

use App\ImportError\Service\ImportErrorService;
use App\ImportJob\Enum\ImportResultEnum;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Repository\ImportJobRepository;
use App\ImportJob\Service\ImportService;
use App\ImportJobItem\Service\ImportJobItemManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
        private ImportJobItemManager $jobItemManager,
        private LoggerInterface $clientLogger,
        private EntityManagerInterface $em,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(ImportMessage $message): void
    {
        $this->em->wrapInTransaction(function () use ($message) {
            try {
                $jobItem = $this->jobItemManager->create($message);
            } catch (UniqueConstraintViolationException) {
                return;
            }

            $context = [
                'domain' => 'client',
                'operation' => 'process_message',
                'job_id' => $message->jobId,
                'email' => $message->dto->email,
            ];

            $this->jobRepo->markProcessing($message->jobId);

            $violations = $this->validator->validate($message->dto, groups: ['import']);

            if (count($violations) > 0) {
                $this->jobRepo->incrementFailed($message->jobId);

                $job = $this->jobRepo->find($message->jobId);

                $jobError = $this->errorService->create(
                    $job,
                    (array) $message->dto,
                    (string) $violations,
                );

                $this->jobItemManager->fail($jobItem, $jobError);

                $this->clientLogger->warning('Import validation failed', $this->ctx($context, 'rejected', [
                    'violations' => (string) $violations,
                ]));
            } else {
                $result = $this->service->import($message->dto);

                if ($result === ImportResultEnum::CREATED) {
                    $this->jobRepo->incrementProcessed($message->jobId);
                    $this->jobItemManager->success($jobItem);
                } else if ($result === ImportResultEnum::SKIPPED) {
                    $this->jobRepo->incrementSkipped($message->jobId);
                    $this->jobItemManager->skip($jobItem);
                }
            }

            $this->jobRepo->markFinishedIfDone($message->jobId);

        });
    }

    private function ctx(array $context, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
            'domain' => 'client',
            'outcome' => $outcome,
        ];
    }
}
