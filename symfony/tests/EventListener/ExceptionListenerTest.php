<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class ExceptionListenerTest extends TestCase
{
    public function testValidationViolationsAreIncludedInJsonResponse(): void
    {
        $input = new class {
            #[Assert\NotBlank(message: 'Email is required.')]
            public string $email = '';
        };

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($input);

        $validationException = new ValidationFailedException($input, $violations);
        $exception = new UnprocessableEntityHttpException(
            'Validation failed',
            $validationException,
        );

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('test');

        $listener = new ExceptionListener(
            $this->createMock(LoggerInterface::class),
            $kernel,
        );
        $event = new ExceptionEvent(
            $kernel,
            Request::create('/api/client/registration/', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $listener($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(422, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);
        self::assertSame(
            [
                'message' => 'Validation failed',
                'violations' => [
                    [
                        'propertyPath' => 'email',
                        'message' => 'Email is required.',
                    ],
                ],
            ],
            json_decode($content, true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
