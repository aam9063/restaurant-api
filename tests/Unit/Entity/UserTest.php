<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testGettersAndSetters(): void
    {
        // Test email
        $email = 'test@ejemplo.com';
        $this->user->setEmail($email);
        $this->assertEquals($email, $this->user->getEmail());

        // Test name
        $name = 'Usuario de Prueba';
        $this->user->setName($name);
        $this->assertEquals($name, $this->user->getName());

        // Test API Key
        $apiKey = 'test_api_key_123456789';
        $this->user->setApiKey($apiKey);
        $this->assertEquals($apiKey, $this->user->getApiKey());

        // Test roles
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $this->user->setRoles($roles);
        $this->assertEquals($roles, $this->user->getRoles());

        // Test isActive
        $this->user->setIsActive(false);
        $this->assertFalse($this->user->isActive());

        $this->user->setIsActive(true);
        $this->assertTrue($this->user->isActive());
    }

    public function testConstructorSetsDefaults(): void
    {
        $user = new User();

        $this->assertTrue($user->isActive());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }

    public function testGenerateApiKey(): void
    {
        $this->user->generateApiKey();

        $apiKey = $this->user->getApiKey();
        $this->assertNotNull($apiKey);
        $this->assertEquals(64, strlen($apiKey)); // bin2hex(random_bytes(32)) = 64 caracteres
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $apiKey);
    }

    public function testGenerateApiKeyCreatesDifferentKeys(): void
    {
        $this->user->generateApiKey();
        $firstKey = $this->user->getApiKey();

        $this->user->generateApiKey();
        $secondKey = $this->user->getApiKey();

        $this->assertNotEquals($firstKey, $secondKey);
    }

    public function testUserInterfaceMethods(): void
    {
        $email = 'test@ejemplo.com';
        $this->user->setEmail($email);

        // getUserIdentifier debería devolver el email
        $this->assertEquals($email, $this->user->getUserIdentifier());

        // getRoles siempre debe incluir ROLE_USER
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testRolesAlwaysIncludeRoleUser(): void
    {
        // Sin roles específicos
        $this->assertContains('ROLE_USER', $this->user->getRoles());

        // Con roles específicos
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testSettersUpdateTimestamp(): void
    {
        $user = new User();
        $originalTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        // Establecer un tiempo específico
        $user->setUpdatedAt($originalTime);
        $this->assertEquals($originalTime, $user->getUpdatedAt());

        // Usar un setter debería actualizar el timestamp
        $user->setName('Nuevo Nombre');

        // Verificar que el timestamp cambió
        $this->assertNotEquals($originalTime, $user->getUpdatedAt());
        $this->assertGreaterThan(
            $originalTime->getTimestamp(),
            $user->getUpdatedAt()->getTimestamp()
        );
    }

    public function testDefaultValues(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getName());
        $this->assertNotNull($user->getApiKey()); // Se genera automáticamente
        $this->assertTrue($user->isActive());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertNotNull($user->getCreatedAt());
        $this->assertNotNull($user->getUpdatedAt());
    }

    public function testFluentInterface(): void
    {
        $result = $this->user
            ->setEmail('test@ejemplo.com')
            ->setName('Test User')
            ->setIsActive(true);

        $this->assertSame($this->user, $result);
        $this->assertEquals('test@ejemplo.com', $this->user->getEmail());
        $this->assertEquals('Test User', $this->user->getName());
        $this->assertTrue($this->user->isActive());
    }

    public function testEraseCredentials(): void
    {
        // El método existe pero no hace nada por ahora
        $this->user->setApiKey('test_key');
        $this->user->eraseCredentials();

        // El API key debería seguir ahí porque no implementamos borrado automático
        $this->assertEquals('test_key', $this->user->getApiKey());
    }

    public function testEmailValidationCanSetAnyValue(): void
    {
        // Este test solo verifica que podemos establecer emails
        // La validación real se maneja por Symfony Validator
        $emails = [
            'test@ejemplo.com',
            'invalid-email',
            '',
        ];

        foreach ($emails as $email) {
            $this->user->setEmail($email);
            $this->assertEquals($email, $this->user->getEmail());
        }
    }

    public function testRolesDuplication(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);
        $roles = $this->user->getRoles();

        // Debería eliminar duplicados y mantener ROLE_USER
        $this->assertCount(2, $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testTimestampsAreImmutable(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->user->getUpdatedAt());
    }
}
