<?php

declare(strict_types=1);

namespace App\ImportJob\MessageHandler;

use App\ImportError\Service\ImportErrorService;
use App\ImportJob\Message\ImportMessage;
use App\ImportJob\Message\SendActivationEmailMessage;
use App\ImportJob\Repository\ImportJobRepository;
use App\ImportJob\Service\ImportService;
use App\ImportJobItem\Service\ImportJobItemManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
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
        private MessageBusInterface $bus,
        private LoggerInterface $clientLogger,
        private EntityManagerInterface $em,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(ImportMessage $message): void
    {
        $emailMessage = null;
        $this->em->wrapInTransaction(function () use ($message, &$emailMessage) {
            $jobItem = $this->jobItemManager->create($message);

            if ($jobItem === null) {
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
                if ($job === null) {
                    return;
                }

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
                $client = $this->service->import($message->dto);

                if ($client !== null) {
                    $this->jobRepo->incrementProcessed($message->jobId);
                    $this->jobItemManager->success($jobItem);

                    $clientId = $client->getId();
                    if ($clientId !== null) {
                        $emailMessage = new SendActivationEmailMessage($clientId);
                    }
                } else {
                    $this->jobRepo->incrementSkipped($message->jobId);
                    $this->jobItemManager->skip($jobItem);
                }
            }

            $this->jobRepo->markFinishedIfDone($message->jobId);

        });

        if ($emailMessage !== null) {
            $this->bus->dispatch($emailMessage);
        }
    }

    /**
     * @param array<string, scalar|null> $context
     * @param array<string, scalar|null> $extra
     * @return array<string, scalar|null>
     */
    private function ctx(array $context, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
            'domain' => 'client',
            'outcome' => $outcome,
        ];
    }
}
