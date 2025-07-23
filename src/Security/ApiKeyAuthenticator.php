<?php

namespace App\Security;

use App\Repository\UserRepository;
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
        private UserRepository $userRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Soportar autenticación para rutas de API
        return str_starts_with($request->getPathInfo(), '/api');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $this->getApiKey($request);
        
        if (null === $apiKey) {
            throw new CustomUserMessageAuthenticationException('API Key no proporcionada');
        }

        $user = $this->userRepository->findByApiKey($apiKey);
        
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('API Key inválida');
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Usuario inactivo');
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
            'code' => Response::HTTP_UNAUTHORIZED
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