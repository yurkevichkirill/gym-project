<?php

declare(strict_types=1);

namespace App\Payment\Factory;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Payment\DTO\GetPayments;
use App\Payment\DTO\PaymentFilter;
use App\Payment\Enum\PaymentStatusEnum;
use App\Request\Utils\RequestParser;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use DateTimeImmutable;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetPaymentsFactory
{
    public function __construct(
        private ClientRepository $clientRepo,
        private TrainerRepository $trainerRepo,
        private RequestParser $parser,
    )
    {}

    public function fromRequest(Request $request, ?Trainer $trainer = null, ?Client $client = null): GetPayments
    {
        if ($client === null) {
            if ($clientId = $request->query->get('clientId')) {
                $client = $this->clientRepo->find((int) $clientId);

                if (!$client) {
                    throw new NotFoundHttpException("Client not found");
                }
            }
        }

        if ($trainer === null) {
            if ($trainerId = $request->query->get('trainerId')) {
                $trainer = $this->trainerRepo->find((int) $trainerId);

                if (!$trainer) {
                    throw new NotFoundHttpException("Trainer not found");
                }
            }
        }

        $statusInput = $request->query->get('status');
        $status = $statusInput ? PaymentStatusEnum::tryFrom($statusInput) : null;

        if ($statusInput && !$status) {
            throw new BadRequestHttpException('Invalid status');
        }

        $minCreatedAt = $this->parser->parseDate($request->query->get('minCreateAt'));
        $maxCreatedAt = $this->parser->parseDate($request->query->get('maxCreateAt'));

        $filter = new PaymentFilter(
            client: $client,
            trainer: $trainer,
            minAmount: $this->parser->toInt($request->query->get('minAmount')),
            maxAmount: $this->parser->toInt($request->query->get('maxAmount')),
            isRefund: $this->parser->toBool($request->query->get('isRefund')),
            status: $status,
            minCreatedAt: $this->parser->parseDate($request->query->get('minCreateAt')),
            maxCreatedAt: $this->parser->parseDate($request->query->get('maxCreateAt')),
        );

        $allowedSortParams = ['amount', 'category', 'paidAt', 'status', 'isRefund', 'createAt'];

        return new GetPayments(
            sort: $this->parser->parseSort($request->query->get('sort', 'paidAt:ASC'), $allowedSortParams),
            filter: $filter,
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page') ?? 1,
            limit: $this->parser->toPositiveInt($request->query->get('limit'), 'limit') ?? 20,
        );
    }
}
