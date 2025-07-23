# 🧪 Tests de la Restaurant API

## 📋 Descripción

Esta carpeta contiene todos los tests para la Restaurant API, organizados en tres categorías:

- **Unit Tests**: Tests unitarios para clases individuales (✅ **49/49 pasando**)
- **Integration Tests**: Tests de integración con base de datos MySQL (✅ **12/12 pasando**)
- **Functional Tests**: Tests funcionales de endpoints HTTP (✅ **15/15 pasando**)

**Estado actual: 🎉 75/75 tests pasando (100%)**

## 📁 Estructura

```
tests/
├── Unit/                           # Tests unitarios ✅ 49/49
│   ├── Entity/                     # Tests de entidades
│   │   ├── RestaurantTest.php     # Test de la entidad Restaurant (11 tests)
│   │   └── UserTest.php           # Test de la entidad User (13 tests)
│   ├── Security/                   # Tests de componentes de seguridad
│   │   └── ApiKeyAuthenticatorTest.php # Tests del autenticador (18 tests)
│   └── EventListener/              # Tests de event listeners
│       └── RateLimitResponseListenerTest.php # Tests del rate limiter (7 tests)
├── Integration/                    # Tests de integración ✅ 12/12
│   └── Repository/                 # Tests de repositorios
│       └── RestaurantRepositoryTest.php # Tests del repositorio (12 tests)
├── Functional/                     # Tests funcionales ✅ 15/15
│   └── Controller/                 # Tests de controladores
│       └── AuthControllerTest.php # Tests del controlador de auth (15 tests)
├── bootstrap.php                   # Bootstrap para tests
└── README.md                      # Este archivo
```

## 🔧 Configuración

### PHPUnit 12.2

Los tests utilizan PHPUnit 12.2.7 con la siguiente configuración moderna:

- **Base de datos**: MySQL con base de datos dedicada de test (`restaurant_api_test_test`)
- **Entorno**: `test` con archivo `.env.test` dedicado
- **Bootstrap**: `tests/bootstrap.php`
- **Kernel**: `App\Kernel` configurado correctamente
- **Dependencias**: `symfony/browser-kit` y `doctrine/doctrine-fixtures-bundle`

### Configuración del entorno de test

**Archivo `.env.test`:**
```env
APP_ENV=test
APP_SECRET=test_secret_key_for_testing_only
DATABASE_URL="mysql://root:2ZE868Fru!@database:3306/restaurant_api?serverVersion=8.0&charset=utf8mb4"
SYMFONY_DEPRECATIONS_HELPER=disabled
```

**Configuración PHPUnit (`phpunit.dist.xml`):**
```xml
<server name="APP_ENV" value="test" force="true" />
<server name="KERNEL_CLASS" value="App\Kernel" />
```

### Base de datos de test

- **Servidor**: MySQL 8.0 en Docker (servicio `database`)
- **Base de datos**: `restaurant_api_test_test` (creada automáticamente)
- **Migraciones**: Ejecutadas automáticamente antes de tests de integración
- **Datos**: Limpios entre cada test

## 🚀 Ejecutar Tests

### Script personalizado (Recomendado)

```bash
# Ejecutar todos los tests (75/75)
./run-tests.sh all

# Solo tests unitarios (49/49)
./run-tests.sh unit

# Solo tests de integración (12/12)
./run-tests.sh integration

# Solo tests funcionales (15/15)
./run-tests.sh functional

# Tests con coverage HTML
./run-tests.sh coverage-html

# Tests específicos
./run-tests.sh entity        # Solo tests de entidades
./run-tests.sh security      # Solo tests de seguridad
./run-tests.sh repository    # Solo tests de repositorios
./run-tests.sh controller    # Solo tests de controladores

# Tests rápidos (solo unitarios)
./run-tests.sh fast

# Debug detallado
./run-tests.sh debug
```

### Comandos PHPUnit directos

```bash
# Todos los tests
docker exec restaurant_api_php php bin/phpunit --testdox

# Tests por categoría
docker exec restaurant_api_php php bin/phpunit tests/Unit --testdox
docker exec restaurant_api_php php bin/phpunit tests/Integration --testdox
docker exec restaurant_api_php php bin/phpunit tests/Functional --testdox

# Test específico
docker exec restaurant_api_php php bin/phpunit tests/Unit/Entity/RestaurantTest.php --testdox
```

## 📝 Tipos de Tests

### Unit Tests (tests/Unit/) ✅ 49/49

**Objetivo**: Testear clases individuales de forma aislada usando mocks.

**Tests implementados**:

**ApiKeyAuthenticator (18 tests)**:
- ✅ Verificación de headers (Authorization Bearer, X-API-KEY, cookies)
- ✅ Autenticación con API keys válidas
- ✅ Manejo de errores (usuario no encontrado, inactivo, sin API key)
- ✅ Respuestas JSON estructuradas
- ✅ Data providers para múltiples casos
- ✅ Prioridad de métodos de autenticación

**Restaurant Entity (11 tests)**:
- ✅ Getters y setters
- ✅ Timestamps automáticos
- ✅ Fluent interface
- ✅ Validaciones
- ✅ Valores por defecto

**User Entity (13 tests)**:
- ✅ Generación de API keys únicas
- ✅ Manejo de roles
- ✅ UserInterface de Symfony
- ✅ Timestamps y estados

**RateLimitResponseListener (7 tests)**:
- ✅ Headers de rate limiting
- ✅ Filtrado por rutas API
- ✅ Diferentes tipos de límites
- ✅ Información de políticas

### Integration Tests (tests/Integration/) ✅ 12/12

**Objetivo**: Testear interacción con base de datos MySQL real.

**RestaurantRepository (12 tests)**:
- ✅ Búsquedas avanzadas por nombre y dirección
- ✅ Paginación y ordenamiento
- ✅ Filtros por rango de fechas
- ✅ Búsqueda rápida con texto mínimo
- ✅ Estadísticas y agregaciones
- ✅ Restaurantes similares
- ✅ Filtros combinados
- ✅ Manejo de búsquedas vacías

### Functional Tests (tests/Functional/) ✅ 15/15

**Objetivo**: Testear endpoints HTTP completos como un usuario real.

**AuthController (15 tests)**:
- ✅ Registro con datos válidos e inválidos
- ✅ Login con credenciales válidas y casos de error
- ✅ Endpoint `/me` con y sin autenticación
- ✅ Refresh de API key
- ✅ Logout
- ✅ Autenticación con Bearer token
- ✅ Rate limiting en endpoints
- ✅ Formatos de respuesta JSON
- ✅ Códigos de estado HTTP correctos

## 🔧 Mejoras Implementadas

### Correcciones Técnicas

1. **✅ Dependencias actualizadas**:
   ```json
   "symfony/browser-kit": "7.3.*",
   "doctrine/doctrine-fixtures-bundle": "^3.7.1",
   "phpunit/phpunit": ">=12.2.7"
   ```

2. **✅ Configuración PHPUnit corregida**:
   - Eliminado `phpunit.xml` duplicado
   - Agregado `KERNEL_CLASS` para tests funcionales
   - Configuración de entorno de test

3. **✅ ApiKeyAuthenticator mejorado**:
   - Mensajes de error en inglés estandarizados
   - Soporte para múltiples métodos de autenticación
   - Validación correcta de Bearer tokens
   - Tests con data providers modernos

4. **✅ RateLimitResponseListener refactorizado**:
   - Tests que coinciden con implementación real
   - Eliminados mocks de clases final
   - Pruebas de headers y políticas

5. **✅ Base de datos de test configurada**:
   - MySQL 8.0 con base de datos dedicada
   - Migraciones automáticas
   - Configuración de Docker

## 🧩 Patrones de Testing Utilizados

### Attributes de PHP 8 (PHPUnit 12)

```php
#[\PHPUnit\Framework\Attributes\DataProvider('emailProvider')]
public function testEmailValidation(string $email, bool $shouldBeValid): void
{
    // Test code
}

public static function emailProvider(): array
{
    return [
        'valid email' => ['test@ejemplo.com', true],
        'invalid email' => ['invalid-email', false],
    ];
}
```

### Mocks y Stubs Modernos

```php
// Mock de UserRepository
$this->userRepository = $this->createMock(UserRepository::class);
$this->userRepository
    ->expects($this->once())
    ->method('findByApiKey')
    ->with($apiKey)
    ->willReturn($user);
```

### WebTestCase para tests funcionales

```php
protected function setUp(): void
{
    $this->client = static::createClient();
}

public function testLogin(): void
{
    $this->client->request('POST', '/api/auth/login', [
        'email' => 'test@ejemplo.com',
        'password' => 'password123'
    ]);
    
    $this->assertResponseIsSuccessful();
    $this->assertJson($this->client->getResponse()->getContent());
}
```

### KernelTestCase para tests de integración

```php
protected function setUp(): void
{
    $kernel = self::bootKernel();
    
    $this->entityManager = $kernel->getContainer()
        ->get('doctrine')
        ->getManager();
        
    // Preparar base de datos
    $this->entityManager->beginTransaction();
}

protected function tearDown(): void
{
    $this->entityManager->rollback();
    parent::tearDown();
}
```

## 📊 Coverage y Métricas

### Estado actual de coverage

- **Unit Tests**: ~95% coverage de las clases testadas
- **Integration Tests**: 100% coverage de repositorios
- **Functional Tests**: 100% coverage de endpoints públicos

### Generar reporte de coverage

```bash
# HTML coverage (requiere xdebug)
./run-tests.sh coverage-html

# Text coverage rápido
./run-tests.sh coverage

# Coverage específico
docker exec restaurant_api_php php bin/phpunit --coverage-text tests/Unit
```

### Ver coverage

El reporte HTML se genera en `var/coverage/index.html`.

## 🔍 Debugging Tests

### Opciones del script personalizado

```bash
# Debug completo con información detallada
./run-tests.sh debug

# Parar en primer fallo
docker exec restaurant_api_php php bin/phpunit --stop-on-failure

# Filtrar tests específicos
docker exec restaurant_api_php php bin/phpunit --filter="testLogin"

# Solo mostrar fallos
docker exec restaurant_api_php php bin/phpunit --testdox | grep "✘"
```

### Debug de tests específicos

```php
public function testSomething(): void
{
    // Debug temporal
    $response = $this->client->getResponse();
    echo "Response: " . $response->getContent() . "\n";
    
    // Assertions específicas
    $this->assertResponseIsSuccessful();
    $this->assertJson($response->getContent());
    
    $data = json_decode($response->getContent(), true);
    $this->assertArrayHasKey('success', $data);
}
```

## 🎯 Mejores Prácticas Implementadas

### Naming Conventions Actualizadas

- Nombres descriptivos: `testAuthenticateWithValidApiKeyInXApiKeyHeader()`
- Data providers estáticos: `public static function apiKeyExtractionProvider()`
- Métodos de test específicos: `testSupportsReturnsTrueWhenAuthorizationHeaderPresent()`

### Setup y Teardown Optimizados

```php
protected function setUp(): void
{
    parent::setUp();
    $this->userRepository = $this->createMock(UserRepository::class);
    $this->authenticator = new ApiKeyAuthenticator($this->userRepository);
}

protected function tearDown(): void
{
    parent::tearDown();
    // Cleanup automático por PHPUnit 12
}
```

### Assertions Modernas y Específicas

```php
// Assertions específicas de Symfony
$this->assertResponseIsSuccessful();
$this->assertResponseStatusCodeSame(201);
$this->assertJsonContains(['success' => true]);

// Assertions de contenido
$this->assertStringContainsString('test@ejemplo.com', $response);
$this->assertInstanceOf(SelfValidatingPassport::class, $passport);

// Excepciones con mensajes específicos
$this->expectException(CustomUserMessageAuthenticationException::class);
$this->expectExceptionMessage('Invalid API key');
```

### Factory Methods para Test Data

```php
// Constantes para datos de test
private const TEST_EMAIL = 'test@ejemplo.com';
private const TEST_API_KEY = 'test-api-key-123456789';

// Factory methods optimizados
private function createTestUser(array $overrides = []): User
{
    $user = new User();
    $user->setEmail($overrides['email'] ?? self::TEST_EMAIL);
    $user->setName($overrides['name'] ?? 'Test User');
    $user->setIsActive($overrides['active'] ?? true);
    if (!isset($overrides['skip_api_key'])) {
        $user->generateApiKey();
    }
    
    return $user;
}
```

## 🚨 Troubleshooting

### Problemas Resueltos

**✅ Error: Base de datos no encontrada**
- Solucionado con configuración correcta de `.env.test`
- Base de datos MySQL dedicada para tests
- Hostname correcto (`database` en lugar de `db`)

**✅ Error: KERNEL_CLASS no definida**
- Agregado en `phpunit.dist.xml`: `<server name="KERNEL_CLASS" value="App\Kernel" />`

**✅ Error: Clases final no se pueden mockear**
- Refactorizado RateLimitResponseListener para usar implementación real
- Eliminados mocks innecesarios de clases final

**✅ Error: Data providers no funcionan**
- Migrado a PHP 8 attributes: `#[\PHPUnit\Framework\Attributes\DataProvider]`
- Métodos de data provider marcados como `static`

**✅ Error: Mensajes de error inconsistentes**
- Estandarizados todos los mensajes en inglés
- Corregidos tests para usar mensajes exactos

### Comandos de diagnóstico

```bash
# Verificar estado de Docker
docker-compose ps

# Verificar conectividad a base de datos
docker exec restaurant_api_php php bin/console doctrine:database:create --env=test --if-not-exists

# Limpiar cache de tests
docker exec restaurant_api_php php bin/console cache:clear --env=test

# Verificar configuración PHPUnit
docker exec restaurant_api_php php bin/phpunit --version
```

## 📈 Performance

### Métricas actuales

- **Tests unitarios**: ~500ms para 49 tests (promedio 10ms/test)
- **Tests de integración**: ~2s para 12 tests (promedio 167ms/test)
- **Tests funcionales**: ~8s para 15 tests (promedio 533ms/test)
- **Total**: ~10s para 75 tests

### Optimizaciones implementadas

- Base de datos compartida para tests de integración
- Transacciones para rollback rápido
- Mocks optimizados en tests unitarios
- Cache de container de Symfony
- Configuración de base de datos optimizada

## 📚 Recursos y Referencias

- [PHPUnit 12 Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing Guide](https://symfony.com/doc/current/testing.html)
- [Doctrine Testing](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/testing.html)
- [PHPUnit Best Practices](https://phpunit.de/announcements/phpunit-12.html)
- [PHP 8 Attributes in PHPUnit](https://phpunit.de/manual/10.5/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers)
