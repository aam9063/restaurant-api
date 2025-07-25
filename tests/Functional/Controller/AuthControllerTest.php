<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Users\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    private $client;

    private EntityManagerInterface $entityManager;

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);

        // Limpiar la base de datos
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testRegisterWithValidData(): void
    {
        $userData = [
            'email' => 'test@ejemplo.com',
            'name' => 'Usuario de Prueba',
            'roles' => ['ROLE_USER'],
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Usuario creado exitosamente', $data['message']);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('api_key', $data['user']);
        $this->assertEquals('test@ejemplo.com', $data['user']['email']);
        $this->assertEquals('Usuario de Prueba', $data['user']['name']);

        // Verificar que el usuario se guardó en la base de datos
        $user = $this->userRepository->findOneBy(['email' => 'test@ejemplo.com']);
        $this->assertNotNull($user);
        $this->assertTrue($user->isActive());
    }

    public function testRegisterWithInvalidEmail(): void
    {
        $userData = [
            'email' => 'invalid-email',
            'name' => 'Usuario de Prueba',
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertArrayHasKey('details', $data);
    }

    public function testRegisterWithMissingData(): void
    {
        $userData = [
            'email' => 'test@ejemplo.com',
            // Falta el nombre
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testRegisterWithDuplicateEmail(): void
    {
        // Crear primer usuario
        $user = new User();
        $user->setEmail('test@ejemplo.com');
        $user->setName('Usuario Existente');
        $user->generateApiKey();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Intentar crear segundo usuario con mismo email
        $userData = [
            'email' => 'test@ejemplo.com',
            'name' => 'Usuario Duplicado',
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertStringContainsString('ya existe', $data['message']);
    }

    public function testLoginWithValidEmail(): void
    {
        // Crear usuario de prueba
        $user = $this->createTestUser('test@ejemplo.com', 'Usuario de Prueba');

        $loginData = [
            'email' => 'test@ejemplo.com',
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Login exitoso', $data['message']);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('api_key', $data);
        $this->assertEquals('test@ejemplo.com', $data['user']['email']);
    }

    public function testLoginWithNonExistentEmail(): void
    {
        $loginData = [
            'email' => 'noexiste@ejemplo.com',
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertStringContainsString('Usuario no encontrado', $data['message']);
    }

    public function testLoginWithInactiveUser(): void
    {
        // Crear usuario inactivo
        $user = $this->createTestUser('inactive@ejemplo.com', 'Usuario Inactivo');
        $user->setIsActive(false);
        $this->entityManager->flush();

        $loginData = [
            'email' => 'inactive@ejemplo.com',
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertStringContainsString('inactivo', $data['message']);
    }

    public function testMeWithValidApiKey(): void
    {
        // Crear usuario de prueba
        $user = $this->createTestUser('test@ejemplo.com', 'Usuario de Prueba');
        $apiKey = $user->getApiKey();

        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_X-API-KEY' => $apiKey]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('test@ejemplo.com', $data['user']['email']);
        $this->assertEquals('Usuario de Prueba', $data['user']['name']);
        $this->assertArrayHasKey('api_key', $data['user']);
    }

    public function testMeWithInvalidApiKey(): void
    {
        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_X-API-KEY' => 'invalid-api-key']
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testMeWithoutApiKey(): void
    {
        $this->client->request('GET', '/api/auth/me');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testRefreshApiKey(): void
    {
        // Crear usuario de prueba
        $user = $this->createTestUser('test@ejemplo.com', 'Usuario de Prueba');
        $originalApiKey = $user->getApiKey();

        $this->client->request(
            'POST',
            '/api/auth/refresh-api-key',
            [],
            [],
            ['HTTP_X-API-KEY' => $originalApiKey]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('API Key renovada exitosamente', $data['message']);
        $this->assertArrayHasKey('api_key', $data);
        $this->assertNotEquals($originalApiKey, $data['api_key']);

        // Verificar que la API Key cambió en la base de datos
        $this->entityManager->refresh($user);
        $this->assertNotEquals($originalApiKey, $user->getApiKey());
        $this->assertEquals($data['api_key'], $user->getApiKey());
    }

    public function testLogout(): void
    {
        $this->client->request('POST', '/api/auth/logout');

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Logout exitoso', $data['message']);
    }

    public function testAuthenticationWithBearerToken(): void
    {
        // Crear usuario de prueba
        $user = $this->createTestUser('test@ejemplo.com', 'Usuario de Prueba');
        $apiKey = $user->getApiKey();

        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $apiKey]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('test@ejemplo.com', $data['user']['email']);
    }

    public function testRateLimitingOnLogin(): void
    {
        // Este test verifica que el rate limiting esté funcionando
        // Hacer múltiples requests rápidos de login fallido

        for ($i = 0; $i < 12; ++$i) { // Más del límite de 10
            $this->client->request(
                'POST',
                '/api/auth/login',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['email' => 'noexiste@ejemplo.com'])
            );
        }

        $response = $this->client->getResponse();

        // Después de exceder el límite, debería recibir 429
        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    public function testJsonResponseFormat(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'noexiste@ejemplo.com'])
        );

        $response = $this->client->getResponse();

        // Verificar que la respuesta es JSON válido
        $this->assertJson($response->getContent());

        // Verificar estructura de respuesta de error
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('timestamp', $data);
    }

    private function createTestUser(string $email, string $name): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->generateApiKey();
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
