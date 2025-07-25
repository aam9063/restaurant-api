<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Restaurant;
use PHPUnit\Framework\TestCase;

class RestaurantTest extends TestCase
{
    private Restaurant $restaurant;

    protected function setUp(): void
    {
        $this->restaurant = new Restaurant();
    }

    public function testGettersAndSetters(): void
    {
        // Test name
        $name = 'Restaurante de Prueba';
        $this->restaurant->setName($name);
        $this->assertEquals($name, $this->restaurant->getName());

        // Test address
        $address = 'Calle Ficticia 123, Madrid';
        $this->restaurant->setAddress($address);
        $this->assertEquals($address, $this->restaurant->getAddress());

        // Test phone
        $phone = '+34 123 456 789';
        $this->restaurant->setPhone($phone);
        $this->assertEquals($phone, $this->restaurant->getPhone());
    }

    public function testConstructorSetsTimestamps(): void
    {
        $restaurant = new Restaurant();

        $this->assertInstanceOf(\DateTimeImmutable::class, $restaurant->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $restaurant->getUpdatedAt());

        // Verificar que las fechas están cerca del momento actual
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $restaurant->getCreatedAt()->getTimestamp();
        $this->assertLessThan(2, $diff); // Menos de 2 segundos de diferencia
    }

    public function testSettersUpdateTimestamp(): void
    {
        $restaurant = new Restaurant();
        $originalTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        // Establecer un tiempo específico
        $restaurant->setUpdatedAt($originalTime);
        $this->assertEquals($originalTime, $restaurant->getUpdatedAt());

        // Usar un setter debería actualizar el timestamp
        $restaurant->setName('Nuevo Nombre');

        // Verificar que el timestamp cambió
        $this->assertNotEquals($originalTime, $restaurant->getUpdatedAt());
        $this->assertGreaterThan(
            $originalTime->getTimestamp(),
            $restaurant->getUpdatedAt()->getTimestamp()
        );
    }

    public function testIdStartsAsNull(): void
    {
        $this->assertNull($this->restaurant->getId());
    }

    public function testDefaultValues(): void
    {
        $restaurant = new Restaurant();

        $this->assertNull($restaurant->getId());
        $this->assertNull($restaurant->getName());
        $this->assertNull($restaurant->getAddress());
        $this->assertNull($restaurant->getPhone());
        $this->assertNotNull($restaurant->getCreatedAt());
        $this->assertNotNull($restaurant->getUpdatedAt());
    }

    public function testSetNameReturnsInstance(): void
    {
        $result = $this->restaurant->setName('Test Name');
        $this->assertSame($this->restaurant, $result);
    }

    public function testSetAddressReturnsInstance(): void
    {
        $result = $this->restaurant->setAddress('Test Address');
        $this->assertSame($this->restaurant, $result);
    }

    public function testSetPhoneReturnsInstance(): void
    {
        $result = $this->restaurant->setPhone('123456789');
        $this->assertSame($this->restaurant, $result);
    }

    public function testChainedSetters(): void
    {
        $result = $this->restaurant
            ->setName('Restaurante Test')
            ->setAddress('Dirección Test')
            ->setPhone('123456789');

        $this->assertSame($this->restaurant, $result);
        $this->assertEquals('Restaurante Test', $this->restaurant->getName());
        $this->assertEquals('Dirección Test', $this->restaurant->getAddress());
        $this->assertEquals('123456789', $this->restaurant->getPhone());
    }

    public function testTimestampsAreImmutable(): void
    {
        $originalCreatedAt = $this->restaurant->getCreatedAt();
        $originalUpdatedAt = $this->restaurant->getUpdatedAt();

        // Las fechas deben ser inmutables
        $this->assertInstanceOf(\DateTimeImmutable::class, $originalCreatedAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $originalUpdatedAt);
    }

    public function testValidationCanSetStringValues(): void
    {
        // Este test verifica que podemos establecer diferentes valores string
        // La validación real se maneja por Symfony Validator
        $stringValues = [
            '',
            'a',
            'Restaurante Normal',
            str_repeat('a', 256),
        ];

        foreach ($stringValues as $value) {
            $this->restaurant->setName($value);
            $this->assertEquals($value, $this->restaurant->getName());
        }
    }
}
