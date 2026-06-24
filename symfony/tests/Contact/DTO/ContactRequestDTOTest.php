<?php

declare(strict_types=1);

namespace App\Tests\Contact\DTO;

use App\Contact\DTO\ContactRequestDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ContactRequestDTOTest extends TestCase
{
    public function testValidContactRequestHasNoViolations(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate(new ContactRequestDTO(
            name: 'Alice Example',
            email: 'alice@example.com',
            message: 'I would like to know more about memberships.',
        ));

        self::assertCount(0, $violations);
    }

    public function testInvalidContactRequestHasFieldViolations(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate(new ContactRequestDTO(
            name: '',
            email: 'not-an-email',
            message: str_repeat('x', 2001),
        ));

        $propertyPaths = [];
        foreach ($violations as $violation) {
            $propertyPaths[] = $violation->getPropertyPath();
        }

        self::assertContains('name', $propertyPaths);
        self::assertContains('email', $propertyPaths);
        self::assertContains('message', $propertyPaths);
    }
}
