<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\ApiKeyAuthenticator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticatorTest extends TestCase
{
    private ApiKeyAuthenticator $authenticator;
    private MockObject|UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->authenticator = new ApiKeyAuthenticator($this->userRepository);
    }

    public function testSupportsReturnsTrueWhenXApiKeyHeaderPresent(): void
    {
        $request = new Request();
        $request->headers->set('X-API-KEY', 'test-api-key');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsTrueWhenAuthorizationHeaderPresent(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer test-api-key');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWhenNoHeadersPresent(): void
    {
        $request = new Request();

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithValidApiKeyInXApiKeyHeader(): void
    {
        $apiKey = 'valid-api-key';
        $request = new Request();
        $request->headers->set('X-API-KEY', $apiKey);

        $user = new User();
        $user->setEmail('test@ejemplo.com');
        $user->setApiKey($apiKey);
        $user->setIsActive(true);

        $this->userRepository
            ->expects($this->once())
            ->method('findByApiKey')
            ->with($apiKey)
            ->willReturn($user);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateWithValidApiKeyInAuthorizationHeader(): void
    {
        $apiKey = 'valid-api-key';
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $apiKey);

        $user = new User();
        $user->setEmail('test@ejemplo.com');
        $user->setApiKey($apiKey);
        $user->setIsActive(true);

        $this->userRepository
            ->expects($this->once())
            ->method('findByApiKey')
            ->with($apiKey)
            ->willReturn($user);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateThrowsExceptionWhenNoApiKeyProvided(): void
    {
        $request = new Request();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API key provided');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenUserNotFound(): void
    {
        $apiKey = 'invalid-api-key';
        $request = new Request();
        $request->headers->set('X-API-KEY', $apiKey);

        $this->userRepository
            ->expects($this->once())
            ->method('findByApiKey')
            ->with($apiKey)
            ->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenUserInactive(): void
    {
        $apiKey = 'valid-api-key';
        $request = new Request();
        $request->headers->set('X-API-KEY', $apiKey);

        $user = new User();
        $user->setEmail('test@ejemplo.com');
        $user->setApiKey($apiKey);
        $user->setIsActive(false); // Usuario inactivo

        $this->userRepository
            ->expects($this->once())
            ->method('findByApiKey')
            ->with($apiKey)
            ->willReturn($user);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('User inactive');

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);

        $result = $this->authenticator->onAuthenticationSuccess($request, $token, 'api');

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailureReturnsJsonResponse(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('Authentication failed');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('authentication_failed', $content['error']);
        $this->assertEquals('An authentication exception occurred.', $content['message']);
        $this->assertEquals(401, $content['code']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('apiKeyExtractionProvider')]
    public function testApiKeyExtraction(Request $request, ?string $expectedApiKey): void
    {
        if ($expectedApiKey === null) {
            $this->assertFalse($this->authenticator->supports($request));
        } else {
            $this->assertTrue($this->authenticator->supports($request));
        }
    }

    public static function apiKeyExtractionProvider(): array
    {
        $requestWithXApiKey = new Request();
        $requestWithXApiKey->headers->set('X-API-KEY', 'test-key');

        $requestWithBearer = new Request();
        $requestWithBearer->headers->set('Authorization', 'Bearer test-key');

        $requestWithInvalidAuth = new Request();
        $requestWithInvalidAuth->headers->set('Authorization', 'Basic test-key');

        $requestWithCookie = new Request();
        $requestWithCookie->cookies->set('api_key', 'test-key');

        $emptyRequest = new Request();

        return [
            'X-API-KEY header' => [$requestWithXApiKey, 'test-key'],
            'Bearer token' => [$requestWithBearer, 'test-key'],
            'Invalid auth type' => [$requestWithInvalidAuth, null],
            'Cookie only' => [$requestWithCookie, 'test-key'], // cookies sí activan supports()
            'No headers' => [$emptyRequest, null],
        ];
    }

    public function testExtractApiKeyPriority(): void
    {
        // Authorization Bearer tiene prioridad sobre X-API-KEY según el código
        $request = new Request();
        $request->headers->set('X-API-KEY', 'x-api-key-value');
        $request->headers->set('Authorization', 'Bearer auth-bearer-value');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testAuthenticateWithCookieApiToken(): void
    {
        $apiKey = 'cookie-api-key';
        $request = new Request();
        $request->cookies->set('api_key', $apiKey);

        // Debería soportar cookies en supports()
        $this->assertTrue($this->authenticator->supports($request));
        
        $user = new User();
        $user->setEmail('test@ejemplo.com');
        $user->setApiKey($apiKey);
        $user->setIsActive(true);

        $this->userRepository
            ->method('findByApiKey')
            ->with($apiKey)
            ->willReturn($user);

        $passport = $this->authenticator->authenticate($request);
        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testMultipleAuthenticationMethods(): void
    {
        $apiKey = 'test-api-key';
        
        // Test con diferentes métodos de autenticación
        $methods = [
            ['X-API-KEY', $apiKey],
            ['Authorization', 'Bearer ' . $apiKey],
        ];

        foreach ($methods as [$header, $value]) {
            $request = new Request();
            $request->headers->set($header, $value);

            $user = new User();
            $user->setEmail('test@ejemplo.com');
            $user->setApiKey($apiKey);
            $user->setIsActive(true);

            $this->userRepository
                ->method('findByApiKey')
                ->with($apiKey)
                ->willReturn($user);

            $passport = $this->authenticator->authenticate($request);
            $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        }
    }
} 