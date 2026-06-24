<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenSSLAsymmetricKey;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CurrentUserFunctionalTest extends WebTestCase
{
    public function testManuallySignedJwtCanBeParsedByLexik(): void
    {
        self::createClient();

        $jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        self::assertInstanceOf(JWTTokenManagerInterface::class, $jwtManager);

        $token = $this->createAccessToken('current_user@example.com', ['ROLE_CLIENT', 'ROLE_USER']);
        $payload = $jwtManager->parse($token);

        self::assertSame('current_user@example.com', $payload['username'] ?? null);
        self::assertSame(['ROLE_CLIENT', 'ROLE_USER'], $payload['roles'] ?? null);
    }

    /**
     * @param list<string> $roles
     */
    private function createAccessToken(string $email, array $roles): string
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        self::assertIsString($projectDir);

        $privateKeyPem = file_get_contents($projectDir . '/config/jwt/private.pem');
        self::assertIsString($privateKeyPem);

        $passphrase = getenv('JWT_PASSPHRASE');
        self::assertIsString($passphrase);

        $privateKey = openssl_pkey_get_private($privateKeyPem, $passphrase);
        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);

        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'RS256',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'iat' => $now,
            'exp' => $now + 3600,
            'roles' => $roles,
            'username' => $email,
        ], JSON_THROW_ON_ERROR));
        $signingInput = $header . '.' . $payload;
        $signature = '';

        self::assertTrue(openssl_sign(
            $signingInput,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256,
        ));

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
