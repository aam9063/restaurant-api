<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RestaurantRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private RestaurantRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Restaurant::class);

        // Limpiar la base de datos
        $this->entityManager->createQuery('DELETE FROM App\Entity\Restaurant')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Cerrar el entity manager para evitar memory leaks
        $this->entityManager->close();
    }

    public function testFindWithAdvancedSearchByName(): void
    {
        // Crear datos de prueba
        $restaurant1 = $this->createRestaurant('Pizzería Napolitana', 'Calle Roma 123', '123456789');
        $restaurant2 = $this->createRestaurant('Burger King', 'Avenida Principal 456', '987654321');
        $restaurant3 = $this->createRestaurant('Pizza Palace', 'Centro Comercial 789', '555123456');

        // Buscar por nombre usando search general (busca en todos los campos)
        $result = $this->repository->findWithAdvancedSearch(
            search: 'Pizza',
            page: 1,
            limit: 10
        );

        $this->assertGreaterThanOrEqual(1, count($result['results']));
        $this->assertGreaterThanOrEqual(1, $result['pagination']['total']);
        
        $names = array_map(fn($r) => $r->getName(), $result['results']);
        $this->assertTrue(
            in_array('Pizzería Napolitana', $names) || in_array('Pizza Palace', $names),
            'Should find at least one pizza restaurant'
        );
    }

    public function testFindWithAdvancedSearchByAddress(): void
    {
        $restaurant1 = $this->createRestaurant('Restaurant 1', 'Calle Centro 123', '123456789');
        $restaurant2 = $this->createRestaurant('Restaurant 2', 'Avenida Norte 456', '987654321');

        $result = $this->repository->findWithAdvancedSearch(
            address: 'centro',
            page: 1,
            limit: 10
        );

        $this->assertCount(1, $result['results']);
        $this->assertEquals('Restaurant 1', $result['results'][0]->getName());
    }

    public function testFindWithAdvancedSearchWithPagination(): void
    {
        // Crear 5 restaurantes
        for ($i = 1; $i <= 5; $i++) {
            $this->createRestaurant("Restaurant $i", "Address $i", "12345678$i");
        }

        // Primera página (2 elementos)
        $result = $this->repository->findWithAdvancedSearch(
            page: 1,
            limit: 2
        );

        $this->assertCount(2, $result['results']);
        $this->assertEquals(5, $result['pagination']['total']);
        $this->assertEquals(3, $result['pagination']['pages']); // ceil(5/2)

        // Segunda página
        $result = $this->repository->findWithAdvancedSearch(
            page: 2,
            limit: 2
        );

        $this->assertCount(2, $result['results']);
    }

    public function testFindWithAdvancedSearchWithOrdering(): void
    {
        $restaurant1 = $this->createRestaurant('Alpha Restaurant', 'Address 1', '123456789');
        $restaurant2 = $this->createRestaurant('Beta Restaurant', 'Address 2', '987654321');
        $restaurant3 = $this->createRestaurant('Charlie Restaurant', 'Address 3', '555123456');

        // Ordenar por nombre ASC
        $result = $this->repository->findWithAdvancedSearch(
            orderBy: 'name',
            orderDirection: 'ASC',
            page: 1,
            limit: 10
        );

        $this->assertEquals('Alpha Restaurant', $result['results'][0]->getName());
        $this->assertEquals('Beta Restaurant', $result['results'][1]->getName());
        $this->assertEquals('Charlie Restaurant', $result['results'][2]->getName());

        // Ordenar por nombre DESC
        $result = $this->repository->findWithAdvancedSearch(
            orderBy: 'name',
            orderDirection: 'DESC',
            page: 1,
            limit: 10
        );

        $this->assertEquals('Charlie Restaurant', $result['results'][0]->getName());
        $this->assertEquals('Beta Restaurant', $result['results'][1]->getName());
        $this->assertEquals('Alpha Restaurant', $result['results'][2]->getName());
    }

    public function testFindWithAdvancedSearchByDateRange(): void
    {
        $restaurant1 = $this->createRestaurant('Restaurant 1', 'Address 1', '123456789');
        
        // Simular que el segundo restaurante fue creado mañana
        $restaurant2 = new Restaurant();
        $restaurant2->setName('Restaurant 2');
        $restaurant2->setAddress('Address 2');
        $restaurant2->setPhone('987654321');
        
        // Usar reflection para establecer fecha de creación futura
        $reflection = new \ReflectionClass($restaurant2);
        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($restaurant2, new \DateTimeImmutable('+1 day'));
        
        $this->entityManager->persist($restaurant2);
        $this->entityManager->flush();

        // Buscar solo restaurantes creados hoy
        $today = (new \DateTime('today'))->format('Y-m-d');
        
        $result = $this->repository->findWithAdvancedSearch(
            createdFrom: $today,
            createdTo: $today,
            page: 1,
            limit: 10
        );

        $this->assertCount(1, $result['results']);
        $this->assertEquals('Restaurant 1', $result['results'][0]->getName());
    }

    public function testQuickSearch(): void
    {
        $restaurant1 = $this->createRestaurant('Pizzería Italiana', 'Centro 123', '123456789');
        $restaurant2 = $this->createRestaurant('Burger Express', 'Norte 456', '987654321');
        $restaurant3 = $this->createRestaurant('Pizza Hut', 'Sur 789', '555123456');

        $result = $this->repository->quickSearch('Pizza', 5);

        $this->assertGreaterThanOrEqual(1, count($result));
        
        // Verificar que devuelve objetos Restaurant
        $this->assertInstanceOf(Restaurant::class, $result[0]);
        $this->assertNotNull($result[0]->getId());
        $this->assertNotNull($result[0]->getName());
        $this->assertNotNull($result[0]->getAddress());
        $this->assertNotNull($result[0]->getPhone());
        
        // Verificar que al menos uno contiene "Pizza" en el nombre
        $names = array_map(fn($r) => $r->getName(), $result);
        $this->assertTrue(
            array_filter($names, fn($name) => stripos($name, 'Pizza') !== false) !== [],
            'Should find at least one restaurant with Pizza in the name'
        );
    }

    public function testQuickSearchWithShortQuery(): void
    {
        $this->createRestaurant('Pizza Palace', 'Centro 123', '123456789');

        // Query muy corta (menos de 2 caracteres)
        $result = $this->repository->quickSearch('p', 5);

        $this->assertEmpty($result);
    }

    public function testGetStatistics(): void
    {
        // Crear algunos restaurantes
        $this->createRestaurant('Restaurant 1', 'Address 1', '123456789');
        $this->createRestaurant('Restaurant 2', 'Address 2', '987654321');

        $stats = $this->repository->getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('created_today', $stats);
        $this->assertArrayHasKey('created_this_week', $stats);
        $this->assertArrayHasKey('created_this_month', $stats);
        $this->assertArrayHasKey('average_per_day', $stats);
        $this->assertArrayHasKey('generated_at', $stats);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(2, $stats['created_today']);
        $this->assertIsFloat($stats['average_per_day']);
    }

    public function testFindSimilarRestaurants(): void
    {
        $referenceRestaurant = $this->createRestaurant('Pizzería Napolitana', 'Centro Histórico 123', '123456789');
        $similarRestaurant1 = $this->createRestaurant('Pizzería Romana', 'Centro Comercial 456', '987654321');
        $similarRestaurant2 = $this->createRestaurant('Pizza Palace', 'Norte 789', '555123456');
        $differentRestaurant = $this->createRestaurant('Burger King', 'Sur 101', '111222333');

        $result = $this->repository->findSimilarRestaurants($referenceRestaurant, 5);

        // Debería encontrar al menos los restaurantes similares por nombre (contienen "Pizza")
        $this->assertGreaterThan(0, count($result));
        
        // Verificar que no incluye el restaurante de referencia
        foreach ($result as $restaurant) {
            $this->assertNotEquals($referenceRestaurant->getId(), $restaurant->getId());
        }
        
        // Verificar que al menos uno de los similares está en los resultados
        $names = array_map(fn($r) => $r->getName(), $result);
        $this->assertTrue(
            in_array('Pizzería Romana', $names) || in_array('Pizza Palace', $names),
            'Should find at least one similar restaurant'
        );
    }

    public function testFindWithAdvancedSearchCombinedFilters(): void
    {
        $restaurant1 = $this->createRestaurant('Pizza Roma', 'Centro Histórico 123', '123456789');
        $restaurant2 = $this->createRestaurant('Pizza Palace', 'Norte Moderno 456', '987654321');
        $restaurant3 = $this->createRestaurant('Burger Pizza', 'Centro Histórico 789', '555123456');

        // Buscar pizza en el centro histórico
        $result = $this->repository->findWithAdvancedSearch(
            search: 'pizza',
            address: 'centro histórico',
            page: 1,
            limit: 10
        );

        $this->assertCount(2, $result['results']);
        
        $names = array_map(fn($r) => $r->getName(), $result['results']);
        $this->assertContains('Pizza Roma', $names);
        $this->assertContains('Burger Pizza', $names);
    }

    public function testEmptySearchReturnsAll(): void
    {
        $restaurant1 = $this->createRestaurant('Restaurant 1', 'Address 1', '123456789');
        $restaurant2 = $this->createRestaurant('Restaurant 2', 'Address 2', '987654321');

        $result = $this->repository->findWithAdvancedSearch(
            page: 1,
            limit: 10
        );

        $this->assertCount(2, $result['results']);
        $this->assertEquals(2, $result['pagination']['total']);
    }

    private function createRestaurant(string $name, string $address, string $phone): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setName($name);
        $restaurant->setAddress($address);
        $restaurant->setPhone($phone);

        $this->entityManager->persist($restaurant);
        $this->entityManager->flush();

        return $restaurant;
    }
} 