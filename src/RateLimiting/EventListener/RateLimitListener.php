<?php

namespace App\RateLimiting\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class RateLimitListener
{
    public function __construct(
        private RateLimiterFactory $apiGeneralLimiter,
        private RateLimiterFactory $apiWriteOperationsLimiter,
        private RateLimiterFactory $loginIpLimiter,
        private RateLimiterFactory $authenticatedUserLimiter,
        private RateLimiterFactory $anonymousUserLimiter,
        private RateLimiterFactory $userRegistrationLimiter,
        private Security $security
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Solo aplicar rate limiting a rutas de API
        if (!str_starts_with($path, '/api')) {
            return;
        }

        // Obtener información del request
        $clientIp = $request->getClientIp();
        $method = $request->getMethod();
        $user = $this->security->getUser();

        // Identificador único para rate limiting
        $identifier = $this->getIdentifier($request, $user);

        // Aplicar rate limiting según el contexto
        $rateLimitResult = $this->applyRateLimit($request, $identifier, $user);

        if (!$rateLimitResult['allowed']) {
            $this->handleRateLimitExceeded($event, $rateLimitResult);

            return;
        }

        // Agregar headers informativos al response
        $this->addRateLimitHeaders($event, $rateLimitResult);
    }

    private function getIdentifier(Request $request, $user): string
    {
        $clientIp = $request->getClientIp();

        // Si el usuario está autenticado, usar su ID + IP para mayor granularidad
        if ($user) {
            return sprintf('user_%d_ip_%s', $user->getId(), $clientIp);
        }

        // Para usuarios anónimos, usar solo IP
        return sprintf('ip_%s', $clientIp);
    }

    private function applyRateLimit(Request $request, string $identifier, $user): array
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod();
        $clientIp = $request->getClientIp();

        // Rate limiting específico para login
        if (str_contains($path, '/api/auth/login')) {
            $limiter = $this->loginIpLimiter->create($clientIp);
            $limit = $limiter->consume();

            return [
                'allowed' => $limit->isAccepted(),
                'remaining' => $limit->getRemainingTokens(),
                'retry_after' => $limit->getRetryAfter(),
                'limit_type' => 'login',
                'limit_value' => 10,
            ];
        }

        // Rate limiting específico para registro
        if (str_contains($path, '/api/auth/register')) {
            $limiter = $this->userRegistrationLimiter->create($clientIp);
            $limit = $limiter->consume();

            return [
                'allowed' => $limit->isAccepted(),
                'remaining' => $limit->getRemainingTokens(),
                'retry_after' => $limit->getRetryAfter(),
                'limit_type' => 'registration',
                'limit_value' => 5,
            ];
        }

        // Rate limiting para operaciones de escritura
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && !str_contains($path, '/api/auth/')) {
            $limiter = $this->apiWriteOperationsLimiter->create($identifier);
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                return [
                    'allowed' => false,
                    'remaining' => $limit->getRemainingTokens(),
                    'retry_after' => $limit->getRetryAfter(),
                    'limit_type' => 'write_operations',
                    'limit_value' => 30,
                ];
            }
        }

        // Rate limiting general basado en autenticación
        if ($user) {
            // Usuario autenticado - límite más permisivo
            $limiter = $this->authenticatedUserLimiter->create($identifier);
            $limit = $limiter->consume();

            return [
                'allowed' => $limit->isAccepted(),
                'remaining' => $limit->getRemainingTokens(),
                'retry_after' => $limit->getRetryAfter(),
                'limit_type' => 'authenticated_user',
                'limit_value' => 200,
            ];
        } else {
            // Usuario anónimo - límite más estricto
            $limiter = $this->anonymousUserLimiter->create($clientIp);
            $limit = $limiter->consume();

            return [
                'allowed' => $limit->isAccepted(),
                'remaining' => $limit->getRemainingTokens(),
                'retry_after' => $limit->getRetryAfter(),
                'limit_type' => 'anonymous_user',
                'limit_value' => 50,
            ];
        }
    }

    private function handleRateLimitExceeded(RequestEvent $event, array $rateLimitResult): void
    {
        $response = new JsonResponse([
            'error' => true,
            'code' => Response::HTTP_TOO_MANY_REQUESTS,
            'message' => 'Rate limit exceeded',
            'details' => sprintf(
                'Too many requests. Limit: %d requests per hour for %s. Try again in %d seconds.',
                $rateLimitResult['limit_value'],
                $rateLimitResult['limit_type'],
                $rateLimitResult['retry_after']?->getTimestamp() - time() ?? 0
            ),
            'limit_type' => $rateLimitResult['limit_type'],
            'retry_after' => $rateLimitResult['retry_after']?->getTimestamp() ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
        ], Response::HTTP_TOO_MANY_REQUESTS);

        // Headers de rate limiting
        $response->headers->set('X-RateLimit-Limit', (string) $rateLimitResult['limit_value']);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Type', $rateLimitResult['limit_type']);

        if ($rateLimitResult['retry_after']) {
            $response->headers->set('Retry-After', (string) ($rateLimitResult['retry_after']->getTimestamp() - time()));
        }

        $event->setResponse($response);
    }

    private function addRateLimitHeaders(RequestEvent $event, array $rateLimitResult): void
    {
        // Los headers se agregarán en el ResponseEvent
        $request = $event->getRequest();
        $request->attributes->set('rate_limit_info', [
            'remaining' => $rateLimitResult['remaining'],
            'limit' => $rateLimitResult['limit_value'],
            'type' => $rateLimitResult['limit_type'],
        ]);
    }
}
