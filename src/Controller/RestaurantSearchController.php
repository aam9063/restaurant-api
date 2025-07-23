<?php

namespace App\Controller;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/restaurants', name: 'api_restaurants_')]
#[OA\Tag(name: 'Restaurant Search', description: 'Búsqueda y filtrado avanzado de restaurantes')]
class RestaurantSearchController extends AbstractController
{
    public function __construct(
        private RestaurantRepository $restaurantRepository
    ) {
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurants/search',
        summary: 'Búsqueda avanzada de restaurantes',
        description: 'Buscar restaurantes con múltiples filtros y opciones de ordenamiento',
        tags: ['Restaurant Search']
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Búsqueda general en nombre, dirección y teléfono',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'pizza')
    )]
    #[OA\Parameter(
        name: 'name',
        in: 'query',
        description: 'Filtrar por nombre del restaurante',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'Pizzería')
    )]
    #[OA\Parameter(
        name: 'address',
        in: 'query',
        description: 'Filtrar por dirección',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'Centro')
    )]
    #[OA\Parameter(
        name: 'phone',
        in: 'query',
        description: 'Filtrar por teléfono',
        required: false,
        schema: new OA\Schema(type: 'string', example: '555')
    )]
    #[OA\Parameter(
        name: 'created_from',
        in: 'query',
        description: 'Fecha de creación desde (YYYY-MM-DD)',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')
    )]
    #[OA\Parameter(
        name: 'created_to',
        in: 'query',
        description: 'Fecha de creación hasta (YYYY-MM-DD)',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2024-12-31')
    )]
    #[OA\Parameter(
        name: 'updated_from',
        in: 'query',
        description: 'Fecha de actualización desde (YYYY-MM-DD)',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')
    )]
    #[OA\Parameter(
        name: 'updated_to',
        in: 'query',
        description: 'Fecha de actualización hasta (YYYY-MM-DD)',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'date', example: '2024-12-31')
    )]
    #[OA\Parameter(
        name: 'order_by',
        in: 'query',
        description: 'Campo por el cual ordenar',
        required: false,
        schema: new OA\Schema(
            type: 'string',
            enum: ['name', 'address', 'phone', 'created_at', 'updated_at'],
            example: 'name'
        )
    )]
    #[OA\Parameter(
        name: 'order_direction',
        in: 'query',
        description: 'Dirección del ordenamiento',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], example: 'ASC')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Número de página para paginación',
        required: false,
        schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Número de resultados por página',
        required: false,
        schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 10)
    )]
    #[Security(name: 'ApiKeyAuth')]
    #[OA\Response(
        response: 200,
        description: 'Resultados de búsqueda',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'results', type: 'array', items: new OA\Items(ref: '#/components/schemas/Restaurant')),
                new OA\Property(property: 'pagination', type: 'object', properties: [
                    new OA\Property(property: 'total', type: 'integer', example: 25),
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'limit', type: 'integer', example: 10),
                    new OA\Property(property: 'pages', type: 'integer', example: 3)
                ]),
                new OA\Property(property: 'filters_applied', type: 'object')
            ]
        )
    )]
    public function search(Request $request): JsonResponse
    {
        // Extraer parámetros de búsqueda
        $criteria = [
            'search' => $request->query->get('search'),
            'name' => $request->query->get('name'),
            'address' => $request->query->get('address'),
            'phone' => $request->query->get('phone'),
            'created_from' => $request->query->get('created_from'),
            'created_to' => $request->query->get('created_to'),
            'updated_from' => $request->query->get('updated_from'),
            'updated_to' => $request->query->get('updated_to'),
            'order_by' => $request->query->get('order_by', 'name'),
            'order_direction' => $request->query->get('order_direction', 'ASC')
        ];

        // Filtrar criterios vacíos
        $criteria = array_filter($criteria, fn($value) => $value !== null && $value !== '');

        // Parámetros de paginación
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 10)));

        // Realizar búsqueda con paginación
        $result = $this->restaurantRepository->findByAdvancedSearchWithPagination($criteria, $page, $limit);

        return new JsonResponse([
            'results' => array_map([$this, 'serializeRestaurant'], $result['results']),
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'pages' => $result['pages']
            ],
            'filters_applied' => $criteria
        ]);
    }

    #[Route('/{id}/similar', name: 'similar', methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurants/{id}/similar',
        summary: 'Encontrar restaurantes similares',
        description: 'Buscar restaurantes similares basado en nombre y dirección',
        tags: ['Restaurant Search']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID del restaurante de referencia',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Número máximo de resultados similares',
        required: false,
        schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 20, example: 5)
    )]
    #[Security(name: 'ApiKeyAuth')]
    #[OA\Response(
        response: 200,
        description: 'Restaurantes similares encontrados',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reference_restaurant', ref: '#/components/schemas/Restaurant'),
                new OA\Property(property: 'similar_restaurants', type: 'array', items: new OA\Items(ref: '#/components/schemas/Restaurant')),
                new OA\Property(property: 'count', type: 'integer', example: 3)
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Restaurante no encontrado'
    )]
    public function findSimilar(int $id, Request $request): JsonResponse
    {
        $restaurant = $this->restaurantRepository->find($id);
        
        if (!$restaurant) {
            return new JsonResponse([
                'error' => 'Restaurante no encontrado',
                'code' => Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        }

        $limit = min(20, max(1, (int) $request->query->get('limit', 5)));
        $similarRestaurants = $this->restaurantRepository->findSimilar($restaurant, $limit);

        return new JsonResponse([
            'reference_restaurant' => $this->serializeRestaurant($restaurant),
            'similar_restaurants' => array_map([$this, 'serializeRestaurant'], $similarRestaurants),
            'count' => count($similarRestaurants)
        ]);
    }

    #[Route('/statistics', name: 'statistics', methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurants/statistics',
        summary: 'Estadísticas de restaurantes',
        description: 'Obtener estadísticas generales sobre los restaurantes',
        tags: ['Restaurant Search']
    )]
    #[Security(name: 'ApiKeyAuth')]
    #[OA\Response(
        response: 200,
        description: 'Estadísticas de restaurantes',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'total', type: 'integer', example: 150, description: 'Total de restaurantes'),
                new OA\Property(property: 'created_today', type: 'integer', example: 3, description: 'Creados hoy'),
                new OA\Property(property: 'created_this_week', type: 'integer', example: 12, description: 'Creados esta semana'),
                new OA\Property(property: 'created_this_month', type: 'integer', example: 45, description: 'Creados este mes'),
                new OA\Property(property: 'average_per_day', type: 'number', format: 'float', example: 1.5, description: 'Promedio por día este mes'),
                new OA\Property(property: 'generated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:45Z')
            ]
        )
    )]
    public function statistics(): JsonResponse
    {
        $stats = $this->restaurantRepository->getStatistics();
        $stats['generated_at'] = (new \DateTimeImmutable())->format('c');

        return new JsonResponse($stats);
    }

    #[Route('/quick-search', name: 'quick_search', methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurants/quick-search',
        summary: 'Búsqueda rápida',
        description: 'Búsqueda rápida sin paginación para autocompletado',
        tags: ['Restaurant Search']
    )]
    #[OA\Parameter(
        name: 'q',
        in: 'query',
        description: 'Término de búsqueda rápida',
        required: true,
        schema: new OA\Schema(type: 'string', minLength: 2, example: 'pizza')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Número máximo de resultados',
        required: false,
        schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, example: 10)
    )]
    #[Security(name: 'ApiKeyAuth')]
    #[OA\Response(
        response: 200,
        description: 'Resultados de búsqueda rápida',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'results', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Pizzería Napolitana'),
                        new OA\Property(property: 'address', type: 'string', example: 'Calle Roma 145'),
                        new OA\Property(property: 'phone', type: 'string', example: '555-0101')
                    ]
                )),
                new OA\Property(property: 'count', type: 'integer', example: 5),
                new OA\Property(property: 'query', type: 'string', example: 'pizza')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Término de búsqueda muy corto'
    )]
    public function quickSearch(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        
        if (strlen($query) < 2) {
            return new JsonResponse([
                'error' => 'El término de búsqueda debe tener al menos 2 caracteres',
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        }

        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        
        $criteria = ['search' => $query];
        $restaurants = $this->restaurantRepository->findByAdvancedSearch($criteria);
        
        // Limitar resultados para búsqueda rápida
        $restaurants = array_slice($restaurants, 0, $limit);
        
        // Serialización ligera para autocompletado
        $results = array_map(function (Restaurant $restaurant) {
            return [
                'id' => $restaurant->getId(),
                'name' => $restaurant->getName(),
                'address' => $restaurant->getAddress(),
                'phone' => $restaurant->getPhone()
            ];
        }, $restaurants);

        return new JsonResponse([
            'results' => $results,
            'count' => count($results),
            'query' => $query
        ]);
    }

    /**
     * Serializar entidad Restaurant para respuesta JSON
     */
    private function serializeRestaurant(Restaurant $restaurant): array
    {
        return [
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'address' => $restaurant->getAddress(),
            'phone' => $restaurant->getPhone(),
            'created_at' => $restaurant->getCreatedAt()?->format('c'),
            'updated_at' => $restaurant->getUpdatedAt()?->format('c')
        ];
    }
} 