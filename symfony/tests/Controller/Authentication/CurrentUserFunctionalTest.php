<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use OpenSSLAsymmetricKey;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CurrentUserFunctionalTest extends WebTestCase
{
    public function testJwtCanBeSignedWithConfiguredPrivateKey(): void
    {
        self::createClient();

        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        self::assertIsString($projectDir);

        $privateKeyPem = file_get_contents($projectDir . '/config/jwt/private.pem');
        self::assertIsString($privateKeyPem);

        $passphrase = getenv('JWT_PASSPHRASE');
        self::assertIsString($passphrase);

        $privateKey = openssl_pkey_get_private($privateKeyPem, $passphrase);
        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);

        $signature = '';
        self::assertTrue(openssl_sign(
            'header.payload',
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256,
        ));
        self::assertNotSame('', $signature);
    }
}
