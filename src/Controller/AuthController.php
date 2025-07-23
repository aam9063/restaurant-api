<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
#[OA\Tag(name: 'Authentication', description: 'Endpoints de autenticación y autorización')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Iniciar sesión',
        description: 'Autentica un usuario por email y devuelve su API Key',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['email'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'usuario@ejemplo.com',
                    description: 'Email del usuario'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Login exitoso',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Login exitoso'),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'usuario@ejemplo.com'),
                        new OA\Property(property: 'name', type: 'string', example: 'Usuario Ejemplo'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])
                    ]
                ),
                new OA\Property(property: 'api_key', type: 'string', example: 'abc123...', description: 'API Key para autenticación')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Email requerido',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Email es requerido'),
                new OA\Property(property: 'code', type: 'integer', example: 400)
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Usuario no encontrado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Usuario no encontrado'),
                new OA\Property(property: 'code', type: 'integer', example: 404)
            ]
        )
    )]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email'])) {
            return new JsonResponse([
                'error' => 'Email es requerido',
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByEmail($data['email']);
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Usuario no encontrado',
                'code' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$user->isActive()) {
            return new JsonResponse([
                'error' => 'Usuario inactivo',
                'code' => Response::HTTP_FORBIDDEN
            ], Response::HTTP_FORBIDDEN);
        }

        // Crear cookie HttpOnly con la API Key
        $cookie = Cookie::create('api_key')
            ->withValue($user->getApiKey())
            ->withExpires(new \DateTime('+1 week'))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $response = new JsonResponse([
            'message' => 'Login exitoso',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles()
            ],
            'api_key' => $user->getApiKey()
        ]);

        $response->headers->setCookie($cookie);
        
        return $response;
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesión',
        description: 'Elimina la cookie de sesión del usuario',
        tags: ['Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'Logout exitoso',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Logout exitoso')
            ]
        )
    )]
    public function logout(): JsonResponse
    {
        // Eliminar la cookie
        $cookie = Cookie::create('api_key')
            ->withValue('')
            ->withExpires(new \DateTime('-1 day'))
            ->withPath('/')
            ->withHttpOnly(true);

        $response = new JsonResponse([
            'message' => 'Logout exitoso'
        ]);

        $response->headers->setCookie($cookie);
        
        return $response;
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Registrar usuario',
        description: 'Crea un nuevo usuario en el sistema',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['email', 'name'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'nuevo@ejemplo.com',
                    description: 'Email del usuario'
                ),
                new OA\Property(
                    property: 'name',
                    type: 'string',
                    example: 'Nuevo Usuario',
                    description: 'Nombre completo del usuario'
                ),
                new OA\Property(
                    property: 'roles',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['ROLE_USER'],
                    description: 'Roles del usuario (opcional)'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Usuario creado exitosamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Usuario creado exitosamente'),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'nuevo@ejemplo.com'),
                        new OA\Property(property: 'name', type: 'string', example: 'Nuevo Usuario'),
                        new OA\Property(property: 'api_key', type: 'string', example: 'def456...'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Error de validación',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Datos de validación incorrectos'),
                new OA\Property(property: 'details', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'code', type: 'integer', example: 400)
            ]
        )
    )]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setName($data['name'] ?? '');
        
        // Si se especifica rol y es admin, asignarlo
        if (isset($data['roles']) && in_array('ROLE_ADMIN', $data['roles'])) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $errors = $this->validator->validate($user);
        
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return new JsonResponse([
                'error' => 'Datos de validación incorrectos',
                'details' => $errorMessages,
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Usuario creado exitosamente',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'api_key' => $user->getApiKey(),
                    'roles' => $user->getRoles()
                ]
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error al crear usuario',
                'details' => $e->getMessage(),
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Obtener usuario actual',
        description: 'Obtiene la información del usuario autenticado',
        tags: ['Authentication']
    )]
    #[Security(name: 'ApiKeyAuth')]
    #[OA\Response(
        response: 200,
        description: 'Información del usuario autenticado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'usuario@ejemplo.com'),
                        new OA\Property(property: 'name', type: 'string', example: 'Usuario Ejemplo'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'api_key', type: 'string', example: 'abc123...'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', example: '2024-01-15 10:30:45'),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2024-01-15 10:30:45')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Usuario no autenticado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Usuario no autenticado'),
                new OA\Property(property: 'code', type: 'integer', example: 401)
            ]
        )
    )]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'error' => 'Usuario no autenticado',
                'code' => Response::HTTP_UNAUTHORIZED
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'api_key' => $user->getApiKey(),
                'is_active' => $user->isActive(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/refresh-api-key', name: 'refresh_api_key', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/refresh-api-key',
        summary: 'Renovar API Key',
        description: 'Genera una nueva API Key para el usuario autenticado',
        tags: ['Authentication']
    )]
    #[Security(name: 'ApiKeyAuth')]
    #[OA\Response(
        response: 200,
        description: 'API Key renovada exitosamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'API Key renovada exitosamente'),
                new OA\Property(property: 'api_key', type: 'string', example: 'xyz789...')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Usuario no autenticado',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Usuario no autenticado'),
                new OA\Property(property: 'code', type: 'integer', example: 401)
            ]
        )
    )]
    public function refreshApiKey(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'error' => 'Usuario no autenticado',
                'code' => Response::HTTP_UNAUTHORIZED
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->generateApiKey();
        $this->entityManager->flush();

        // Actualizar cookie con nueva API Key
        $cookie = Cookie::create('api_key')
            ->withValue($user->getApiKey())
            ->withExpires(new \DateTime('+1 week'))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $response = new JsonResponse([
            'message' => 'API Key renovada exitosamente',
            'api_key' => $user->getApiKey()
        ]);

        $response->headers->setCookie($cookie);
        
        return $response;
    }
} 