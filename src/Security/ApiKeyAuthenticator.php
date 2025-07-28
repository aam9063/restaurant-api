<?php

namespace App\Security;

use App\Service\ApiKeyService;
use App\Users\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private ApiKeyService $apiKeyService
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Verificar si hay headers de autenticación presentes y válidos
        $authHeader = $request->headers->get('Authorization');
        $hasValidAuth = $authHeader && str_starts_with($authHeader, 'Bearer ');

        return $hasValidAuth
            || $request->headers->has('X-API-KEY')
            || $request->cookies->has('api_key');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $this->getApiKey($request);

        if (null === $apiKey) {
            throw new CustomUserMessageAuthenticationException('No API key provided');
        }

        // Validar formato de API key antes de buscar en BD
        if (!$this->apiKeyService->isValidApiKeyFormat($apiKey)) {
            throw new CustomUserMessageAuthenticationException('Invalid API key format');
        }

        $user = $this->userRepository->findByApiKey($apiKey);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('User inactive');
        }

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Permite que la request continúe
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
            'error' => 'authentication_failed',
            'code' => Response::HTTP_UNAUTHORIZED,
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    private function getApiKey(Request $request): ?string
    {
        // Primero intentar obtener la API Key desde el header Authorization
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Luego intentar obtenerla desde el header X-API-KEY
        $apiKeyHeader = $request->headers->get('X-API-KEY');
        if ($apiKeyHeader) {
            return $apiKeyHeader;
        }

        // Finalmente intentar obtenerla desde las cookies
        return $request->cookies->get('api_key');
    }
}
