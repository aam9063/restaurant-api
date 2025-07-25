<?php

namespace App\RateLimiting\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: 0)]
class RateLimitResponseListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Solo agregar headers a rutas de API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Obtener información de rate limiting del request
        $rateLimitInfo = $request->attributes->get('rate_limit_info');

        if ($rateLimitInfo) {
            $response->headers->set('X-RateLimit-Limit', (string) $rateLimitInfo['limit']);
            $response->headers->set('X-RateLimit-Remaining', (string) $rateLimitInfo['remaining']);
            $response->headers->set('X-RateLimit-Type', $rateLimitInfo['type']);

            // Agregar información adicional sobre el tipo de límite
            $response->headers->set('X-RateLimit-Policy', $this->getRateLimitPolicy($rateLimitInfo['type']));
        }

        // Agregar headers CORS para rate limiting
        $response->headers->set(
            'Access-Control-Expose-Headers',
            'X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Type, X-RateLimit-Policy, Retry-After'
        );
    }

    private function getRateLimitPolicy(string $limitType): string
    {
        return match ($limitType) {
            'login' => '10 requests per 15 minutes',
            'registration' => '5 requests per hour',
            'write_operations' => '30 requests per 10 minutes',
            'authenticated_user' => '200 requests per hour',
            'anonymous_user' => '50 requests per hour',
            default => 'Unknown policy'
        };
    }
}
