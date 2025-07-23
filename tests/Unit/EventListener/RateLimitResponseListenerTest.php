<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\RateLimitResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RateLimitResponseListenerTest extends TestCase
{
    private RateLimitResponseListener $listener;

    protected function setUp(): void
    {
        $this->listener = new RateLimitResponseListener();
    }

    public function testOnKernelResponseIgnoresNonMainRequests(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/test');
        
        $response = new Response();
        
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST, // No es main request
            $response
        );

        $this->listener->onKernelResponse($event);

        // No debe agregar headers para sub-requests
        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
    }

    public function testOnKernelResponseIgnoresNonApiRoutes(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/home');
        
        $response = new Response();
        
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        // No debe agregar headers para rutas no-API
        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
    }

    public function testOnKernelResponseAddsHeadersForAuthenticatedUser(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/restaurants');
        $request->attributes->set('rate_limit_info', [
            'limit' => 200,
            'remaining' => 150,
            'type' => 'authenticated_user'
        ]);
        
        $response = new Response();
        
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        $this->assertEquals('200', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('150', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals('authenticated_user', $response->headers->get('X-RateLimit-Type'));
        $this->assertEquals('200 requests per hour', $response->headers->get('X-RateLimit-Policy'));
        $this->assertTrue($response->headers->has('Access-Control-Expose-Headers'));
    }

    public function testOnKernelResponseAddsHeadersForAnonymousUser(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/auth/login');
        $request->attributes->set('rate_limit_info', [
            'limit' => 50,
            'remaining' => 40,
            'type' => 'anonymous_user'
        ]);
        
        $response = new Response();
        
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        $this->assertEquals('50', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('40', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals('anonymous_user', $response->headers->get('X-RateLimit-Type'));
        $this->assertEquals('50 requests per hour', $response->headers->get('X-RateLimit-Policy'));
    }

    public function testOnKernelResponseAddsWarningWhenApproachingLimit(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/auth/login');
        $request->attributes->set('rate_limit_info', [
            'limit' => 10,
            'remaining' => 2, // Cerca del límite
            'type' => 'login'
        ]);
        
        $response = new Response();
        
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        $this->assertEquals('10', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('2', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals('login', $response->headers->get('X-RateLimit-Type'));
        $this->assertEquals('10 requests per 15 minutes', $response->headers->get('X-RateLimit-Policy'));
    }

    public function testOnKernelResponseNoWarningWhenNotApproachingLimit(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/restaurants');
        $request->attributes->set('rate_limit_info', [
            'limit' => 100,
            'remaining' => 90, // Lejos del límite
            'type' => 'write_operations'
        ]);
        
        $response = new Response();
        
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        $this->assertEquals('100', $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('90', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals('write_operations', $response->headers->get('X-RateLimit-Type'));
        $this->assertEquals('30 requests per 10 minutes', $response->headers->get('X-RateLimit-Policy'));
    }

    public function testOnKernelResponseHandlesVariousApiPaths(): void
    {
        $paths = [
            '/api/auth/register',
            '/api/restaurants/search',
            '/api/user/me'
        ];

        foreach ($paths as $path) {
            $request = new Request();
            $request->server->set('REQUEST_URI', $path);
            $request->attributes->set('rate_limit_info', [
                'limit' => 50,
                'remaining' => 25,
                'type' => 'registration'
            ]);
            
            $response = new Response();
            
            $event = new ResponseEvent(
                $this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $response
            );

            $this->listener->onKernelResponse($event);

            $this->assertEquals('50', $response->headers->get('X-RateLimit-Limit'));
            $this->assertEquals('25', $response->headers->get('X-RateLimit-Remaining'));
            $this->assertEquals('registration', $response->headers->get('X-RateLimit-Type'));
            $this->assertEquals('5 requests per hour', $response->headers->get('X-RateLimit-Policy'));
        }
    }
} 