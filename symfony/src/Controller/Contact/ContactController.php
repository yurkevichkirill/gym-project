<?php

declare(strict_types=1);

namespace App\Controller\Contact;

use App\Contact\DTO\ContactRequestDTO;
use App\Contact\Service\ContactMessageService;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/api/contact/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'submitContactForm',
        summary: 'Send a contact request to Evogym.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: ContactRequestDTO::class)),
        ),
        tags: ['Contact'],
        responses: [
            new OA\Response(response: 204, description: 'Contact request accepted'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
            ),
            new OA\Response(
                response: 429,
                description: 'Too many contact requests',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
            ),
            new OA\Response(
                response: 503,
                description: 'Contact delivery is temporarily unavailable',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class)),
            ),
        ],
    )]
    public function submit(
        #[MapRequestPayload] ContactRequestDTO $requestDto,
        ContactMessageService $contactMessageService,
    ): Response {
        try {
            $contactMessageService->send($requestDto);
        } catch (TransportExceptionInterface $exception) {
            throw new ServiceUnavailableHttpException(
                retryAfter: null,
                message: 'Contact service is temporarily unavailable.',
                previous: $exception,
            );
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
