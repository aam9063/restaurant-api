<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorController extends AbstractController
{
    public function show(\Throwable $exception, Request $request): Response
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Error interno del servidor';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        }

        $errorData = $this->getErrorData($statusCode, $message, $exception);

        if (str_starts_with($request->getPathInfo(), '/api')
            || $request->headers->get('Content-Type') === 'application/json'
            || str_contains($request->headers->get('Accept', ''), 'application/json')) {

            return new JsonResponse($errorData, $statusCode);
        }

        return new JsonResponse($errorData, $statusCode);
    }

    private function getErrorData(int $statusCode, string $message, \Throwable $exception): array
    {
        $errorData = [
            'error' => true,
            'code' => $statusCode,
            'message' => $this->getStatusMessage($statusCode),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        switch ($statusCode) {
            case Response::HTTP_BAD_REQUEST:
                $errorData['message'] = 'Solicitud incorrecta';
                $errorData['details'] = $message ?: 'Los datos proporcionados no son válidos';

                break;

            case Response::HTTP_UNAUTHORIZED:
                $errorData['message'] = 'No autorizado';
                $errorData['details'] = 'Se requiere autenticación válida para acceder a este recurso';

                break;

            case Response::HTTP_FORBIDDEN:
                $errorData['message'] = 'Acceso prohibido';
                $errorData['details'] = 'No tienes permisos para acceder a este recurso';

                break;

            case Response::HTTP_NOT_FOUND:
                $errorData['message'] = 'Recurso no encontrado';
                $errorData['details'] = 'El recurso solicitado no existe';

                break;

            case Response::HTTP_METHOD_NOT_ALLOWED:
                $errorData['message'] = 'Método no permitido';
                $errorData['details'] = 'El método HTTP utilizado no está permitido para este endpoint';

                break;

            case Response::HTTP_TOO_MANY_REQUESTS:
                $errorData['message'] = 'Demasiadas solicitudes';
                $errorData['details'] = 'Has excedido el límite de solicitudes. Intenta de nuevo más tarde';

                break;

            case Response::HTTP_UNPROCESSABLE_ENTITY:
                $errorData['message'] = 'Datos no procesables';
                $errorData['details'] = $message ?: 'Los datos proporcionados no pudieron ser procesados';

                break;

            case Response::HTTP_INTERNAL_SERVER_ERROR:
                $errorData['message'] = 'Error interno del servidor';
                $errorData['details'] = 'Ocurrió un error inesperado. Por favor intenta de nuevo más tarde';

                break;

            default:
                $errorData['details'] = $message ?: 'Error no especificado';
        }

        if ($_ENV['APP_ENV'] === 'dev') {
            $errorData['debug'] = [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => array_slice($exception->getTrace(), 0, 3), // Solo primeras 3 líneas del stack trace
            ];
        }

        return $errorData;
    }

    private function getStatusMessage(int $statusCode): string
    {
        return match($statusCode) {
            Response::HTTP_BAD_REQUEST => 'Bad Request',
            Response::HTTP_UNAUTHORIZED => 'Unauthorized',
            Response::HTTP_FORBIDDEN => 'Forbidden',
            Response::HTTP_NOT_FOUND => 'Not Found',
            Response::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            Response::HTTP_TOO_MANY_REQUESTS => 'Too Many Requests',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            Response::HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error',
            default => 'Unknown Error'
        };
    }
}
