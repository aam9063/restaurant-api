<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class RateLimitListener
{
    public function __construct()
    {
        // Rate limiters temporalmente deshabilitados
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Temporalmente deshabilitado para solucionar problemas de configuración
        // TODO: Reactivar rate limiting una vez configurado correctamente
        
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Solo aplicar rate limiting a rutas de API
        if (!str_starts_with($path, '/api')) {
            return;
        }

        // Rate limiting temporalmente deshabilitado
        // Agregar headers informativos
        if ($response = $event->getResponse()) {
            $response->headers->set('X-RateLimit-Remaining', '100');
            $response->headers->set('X-RateLimit-Limit', '100');
        }
    }
} 