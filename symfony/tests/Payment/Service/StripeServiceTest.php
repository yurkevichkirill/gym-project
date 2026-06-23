<?php

declare(strict_types=1);

namespace App\Tests\Payment\Service;

use App\Payment\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class StripeServiceTest extends TestCase
{
    public function testCanBeConstructedWithSupportedStripeConfiguration(): void
    {
        $service = new StripeService(
            stripeSecretKey: 'sk_test_ci',
            em: $this->createMock(EntityManagerInterface::class),
            stripeLogger: $this->createMock(LoggerInterface::class),
        );

        self::assertInstanceOf(StripeService::class, $service);
    }
}
