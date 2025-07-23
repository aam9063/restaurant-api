# 🍽️ Restaurant API - Prueba Técnica Backend

[![Symfony](https://img.shields.io/badge/Symfony-7.3-000000.svg?style=flat&logo=symfony)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4.svg?style=flat&logo=php)](https://php.net/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED.svg?style=flat&logo=docker)](https://docker.com/)
[![API Platform](https://img.shields.io/badge/API%20Platform-4.x-38A3A5.svg?style=flat)](https://api-platform.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1.svg?style=flat&logo=mysql)](https://mysql.com/)

## 📝 Descripción del Proyecto

API RESTful para la gestión de restaurantes desarrollada como prueba técnica backend. El sistema permite realizar operaciones CRUD completas sobre restaurantes con un sistema de autenticación robusto basado en API Keys y cookies HttpOnly.

### 🎯 Características Principales

- ✅ **CRUD Completo** para restaurantes (Crear, Leer, Actualizar, Eliminar)
- 🔐 **Autenticación** con API Key y cookies HttpOnly
- 📚 **Documentación Automática** con Swagger UI
- 🐳 **Dockerización** completa con Docker Compose
- 🛡️ **Validaciones** robustas en entidades
- 🚀 **Rate Limiting Inteligente** por usuario/IP con límites diferenciados
- 🔍 **Búsqueda Avanzada** con múltiples filtros y ordenamiento
- 🔄 **CORS** configurado para frontends
- 📊 **Paginación** automática en listados
- 🎨 **Gestión de Errores** centralizada
- 📈 **Estadísticas** y métricas del sistema
- ⚡ **Búsqueda Rápida** para autocompletado

---

## 🛠️ Tecnologías Utilizadas

| Tecnología | Versión | Descripción |
|------------|---------|-------------|
| **Symfony** | 7.3 | Framework PHP principal |
| **API Platform** | 4.x | Construcción de APIs RESTful |
| **Doctrine ORM** | 3.x | Mapeo objeto-relacional |
| **MySQL** | 8.0 | Base de datos principal |
| **PHP** | 8.3 | Lenguaje de programación |
| **Docker** | Compose | Contenedorización |
| **Nginx** | Alpine | Servidor web |
| **NelmioApiDocBundle** | 5.4 | Documentación OpenAPI |

---

## 📋 Requisitos Previos

- **Docker** y **Docker Compose** instalados
- **Git** para clonar el repositorio
- Puerto **8080** disponible para la aplicación
- Puerto **3307** disponible para MySQL

---

## 🚀 Instalación y Configuración

### 1. Crear Proyecto Symfony desde Cero

```bash
# Instalar Symfony CLI
curl -sS https://get.symfony.com/cli/installer | bash

# Crear nuevo proyecto Symfony
symfony new restaurant-api --version=7.3

# Navegar al directorio
cd restaurant-api
```

### 2. Clonar y Configurar Proyecto Existente

```bash
# Clonar el repositorio
git clone <repository-url>
cd backend

# Construir y ejecutar contenedores
docker-compose up -d --build

# Instalar dependencias
docker exec restaurant_api_php composer install

# Ejecutar migraciones
docker exec restaurant_api_php php bin/console doctrine:migrations:migrate -n

# Limpiar cache
docker exec restaurant_api_php php bin/console cache:clear
```

### 3. Configuración de Variables de Entorno

Crear archivo `.env` en la raíz del proyecto:

```env
# Configuración de Aplicación
APP_ENV=dev
APP_SECRET=e8b5f7c2d9a4e6f1b8c3d7e9f2a5b8c1d4e7f0a3b6c9e2f5a8b1d4e7f0a3b6c9

# Base de Datos
DATABASE_URL="mysql://root:rootpassword@database:3306/restaurant_api?serverVersion=8.0.32"
MYSQL_ROOT_PASSWORD=rootpassword

# Configuración CORS
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$

# API Keys
NELMIO_CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

---

## 📦 Paquetes e Instalación Paso a Paso

### Dependencias Principales

```bash
# API Platform para APIs RESTful
composer require api-platform/core

# Doctrine para ORM y base de datos
composer require symfony/orm-pack
composer require doctrine/doctrine-migrations-bundle

# Seguridad y autenticación
composer require symfony/security-bundle

# Validaciones
composer require symfony/validator

# Serialización
composer require symfony/serializer

# Documentación API
composer require nelmio/api-doc-bundle
composer require nelmio/cors-bundle

# Rate Limiting
composer require symfony/rate-limiter

# Twig para templates (Swagger UI)
composer require symfony/twig-bundle
composer require symfony/asset

# Web Profiler (desarrollo)
composer require --dev symfony/web-profiler-bundle
composer require --dev symfony/maker-bundle
```

### Configuración de Bundles

El archivo `config/bundles.php` debe contener:

```php
<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Nelmio\ApiDocBundle\NelmioApiDocBundle::class => ['all' => true],
    Nelmio\CorsBundle\NelmioCorsBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
];
```

---

## 🐳 Configuración Docker

### Estructura de Docker

```
├── docker-compose.yml
├── Dockerfile
└── docker/
    └── nginx/
        └── default.conf
```

### Docker Compose

```yaml
services:
  php:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: restaurant_api_php
    restart: unless-stopped
    volumes:
      - ./:/var/www/html:cached
    depends_on:
      - database
    networks:
      - restaurant_network

  nginx:
    image: nginx:alpine
    container_name: restaurant_api_nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html:cached
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php
    networks:
      - restaurant_network

  database:
    image: mysql:8.0
    container_name: restaurant_api_mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: restaurant_api
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - restaurant_network
    ports:
      - "3307:3306"

networks:
  restaurant_network:
    driver: bridge

volumes:
  db_data:
```

### Dockerfile

```dockerfile
FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip

# Instalar extensiones PHP
RUN docker-php-ext-install pdo pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/var

EXPOSE 9000
CMD ["php-fpm"]
```

### Comandos Docker Útiles

```bash
# Construir y ejecutar contenedores
docker-compose up -d --build

# Ver logs
docker-compose logs -f

# Acceder al contenedor PHP
docker exec -it restaurant_api_php sh

# Acceder a MySQL
docker exec -it restaurant_api_mysql mysql -u root -p restaurant_api

# Parar contenedores
docker-compose down

# Limpiar todo (contenedores, volúmenes, imágenes)
docker-compose down -v --rmi all
```

---

## 🏗️ Arquitectura del Proyecto

### Estructura de Directorios

```
backend/
├── config/                 # Configuraciones
│   ├── packages/           # Configuración de bundles
│   ├── routes/            # Rutas
│   └── services.yaml      # Servicios
├── migrations/            # Migraciones de base de datos
├── public/               # Archivos públicos
├── src/
│   ├── Controller/       # Controladores
│   ├── Entity/          # Entidades Doctrine
│   ├── Repository/      # Repositorios
│   ├── Security/        # Autenticación personalizada
│   └── EventListener/   # Event listeners (Rate limiting, etc.)
├── templates/           # Plantillas Twig
├── docker/             # Configuración Docker
├── var/               # Cache y logs
└── vendor/           # Dependencias de Composer
```

### Entidades Principales

#### Restaurant Entity
```php
class Restaurant
{
    private ?int $id = null;
    private ?string $name = null;        // Nombre del restaurante
    private ?string $address = null;     // Dirección
    private ?string $phone = null;       // Teléfono
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;
}
```

#### User Entity
```php
class User implements UserInterface
{
    private ?int $id = null;
    private ?string $email = null;       // Email único
    private ?string $name = null;        // Nombre completo
    private ?string $apiKey = null;      // API Key para autenticación
    private array $roles = [];           // Roles del usuario
    private bool $isActive = true;       // Estado del usuario
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;
}
```

---

## 🚫 Rate Limiting Inteligente

### Sistema de Rate Limiting por Usuario/IP

La API implementa un sistema de rate limiting inteligente que aplica diferentes límites según el tipo de usuario y operación:

#### Límites Configurados

| Tipo de Usuario/Operación | Límite | Intervalo | Descripción |
|---------------------------|--------|-----------|-------------|
| **Login por IP** | 10 requests | 15 minutos | Previene ataques de fuerza bruta |
| **Registro por IP** | 5 requests | 1 hora | Previene spam de registros |
| **Operaciones de escritura** | 30 requests | 10 minutos | POST, PUT, PATCH, DELETE |
| **Usuario autenticado** | 200 requests | 1 hora | Límite general más permisivo |
| **Usuario anónimo** | 50 requests | 1 hora | Límite general más estricto |

#### Headers de Rate Limiting

Todas las respuestas incluyen headers informativos:

```http
X-RateLimit-Limit: 200
X-RateLimit-Remaining: 195
X-RateLimit-Type: authenticated_user
X-RateLimit-Policy: 200 requests per hour
Retry-After: 3600 (cuando se excede el límite)
```

#### Respuesta cuando se excede el límite

```json
{
  "error": true,
  "code": 429,
  "message": "Rate limit exceeded",
  "details": "Too many requests. Limit: 200 requests per hour for authenticated_user. Try again in 3600 seconds.",
  "limit_type": "authenticated_user",
  "retry_after": 1642284845,
  "timestamp": "2024-01-15 10:30:45"
}
```

---

## 🔌 Documentación de Endpoints

### Base URL
```
http://localhost:8080/api
```

### 🔐 Endpoints de Autenticación

#### POST `/api/auth/register`
Crear un nuevo usuario en el sistema.

**Request Body:**
```json
{
  "email": "usuario@ejemplo.com",
  "name": "Nombre Usuario",
  "roles": ["ROLE_USER"]  // Opcional
}
```

**Response (201):**
```json
{
  "message": "Usuario creado exitosamente",
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "name": "Nombre Usuario",
    "api_key": "58437522a95dd2c7be83c4a87d172f9fe680f5aefae082345747f9bfbc68a52c",
    "roles": ["ROLE_USER"]
  }
}
```

#### POST `/api/auth/login`
Autenticar usuario y obtener API Key.

**Request Body:**
```json
{
  "email": "usuario@ejemplo.com"
}
```

**Response (200):**
```json
{
  "message": "Login exitoso",
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "name": "Usuario Ejemplo",
    "roles": ["ROLE_USER"]
  },
  "api_key": "58437522a95dd2c7be83c4a87d172f9fe680f5aefae082345747f9bfbc68a52c"
}
```

#### GET `/api/auth/me` 🔒
Obtener información del usuario autenticado.

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "name": "Usuario Ejemplo",
    "roles": ["ROLE_USER"],
    "api_key": "58437522a95dd2c7be83c4a87d172f9fe680f5aefae082345747f9bfbc68a52c",
    "is_active": true,
    "created_at": "2024-01-15 10:30:45",
    "updated_at": "2024-01-15 10:30:45"
  }
}
```

#### POST `/api/auth/refresh-api-key` 🔒
Generar una nueva API Key.

**Headers:**
```
X-API-KEY: your_current_api_key
```

**Response (200):**
```json
{
  "message": "API Key renovada exitosamente",
  "api_key": "new_api_key_here"
}
```

#### POST `/api/auth/logout`
Cerrar sesión y eliminar cookies.

**Response (200):**
```json
{
  "message": "Logout exitoso"
}
```

---

### 🍽️ Endpoints de Restaurantes CRUD

Todos los endpoints de restaurantes requieren autenticación con `X-API-KEY`.

#### GET `/api/restaurants` 🔒
Listar todos los restaurantes con paginación.

**Query Parameters:**
- `page`: Número de página (default: 1)
- `itemsPerPage`: Items por página (default: 10, max: 100)

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Response (200):**
```json
{
  "@context": "/api/contexts/Restaurant",
  "@id": "/api/restaurants",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/restaurants/1",
      "@type": "Restaurant",
      "id": 1,
      "name": "Restaurante Ejemplo",
      "address": "Calle Principal 123",
      "phone": "123456789",
      "createdAt": "2024-01-15T10:30:45+00:00",
      "updatedAt": "2024-01-15T10:30:45+00:00"
    }
  ],
  "hydra:totalItems": 1,
  "hydra:view": {
    "@id": "/api/restaurants?page=1",
    "@type": "hydra:PartialCollectionView"
  }
}
```

#### POST `/api/restaurants` 🔒
Crear un nuevo restaurante.

**Headers:**
```
X-API-KEY: your_api_key_here
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Nuevo Restaurante",
  "address": "Avenida Nueva 456",
  "phone": "987654321"
}
```

**Response (201):**
```json
{
  "@context": "/api/contexts/Restaurant",
  "@id": "/api/restaurants/2",
  "@type": "Restaurant",
  "id": 2,
  "name": "Nuevo Restaurante",
  "address": "Avenida Nueva 456",
  "phone": "987654321",
  "createdAt": "2024-01-15T11:00:00+00:00",
  "updatedAt": "2024-01-15T11:00:00+00:00"
}
```

#### GET `/api/restaurants/{id}` 🔒
Obtener un restaurante específico.

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Response (200):**
```json
{
  "@context": "/api/contexts/Restaurant",
  "@id": "/api/restaurants/1",
  "@type": "Restaurant",
  "id": 1,
  "name": "Restaurante Ejemplo",
  "address": "Calle Principal 123",
  "phone": "123456789",
  "createdAt": "2024-01-15T10:30:45+00:00",
  "updatedAt": "2024-01-15T10:30:45+00:00"
}
```

#### PUT `/api/restaurants/{id}` 🔒
Actualizar completamente un restaurante.

**Headers:**
```
X-API-KEY: your_api_key_here
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Restaurante Actualizado",
  "address": "Calle Actualizada 789",
  "phone": "111222333"
}
```

#### PATCH `/api/restaurants/{id}` 🔒
Actualizar parcialmente un restaurante.

**Headers:**
```
X-API-KEY: your_api_key_here
Content-Type: application/merge-patch+json
```

**Request Body:**
```json
{
  "name": "Solo Nuevo Nombre"
}
```

#### DELETE `/api/restaurants/{id}` 🔒
Eliminar un restaurante.

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Response (204):** Sin contenido

---

### 🔍 Endpoints de Búsqueda Avanzada

#### GET `/api/restaurants/search` 🔒
Búsqueda avanzada con múltiples filtros y paginación.

**Query Parameters:**
- `search`: Búsqueda general en nombre, dirección y teléfono
- `name`: Filtro específico por nombre del restaurante
- `address`: Filtro específico por dirección
- `phone`: Filtro específico por teléfono
- `created_from`: Fecha de creación desde (YYYY-MM-DD)
- `created_to`: Fecha de creación hasta (YYYY-MM-DD)
- `updated_from`: Fecha de actualización desde (YYYY-MM-DD)
- `updated_to`: Fecha de actualización hasta (YYYY-MM-DD)
- `order_by`: Campo de ordenamiento (name, address, phone, created_at, updated_at)
- `order_direction`: Dirección del ordenamiento (ASC, DESC)
- `page`: Número de página para paginación
- `limit`: Número de resultados por página (max 100)

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Ejemplo de uso:**
```bash
GET /api/restaurants/search?search=pizza&order_by=created_at&order_direction=DESC&page=1&limit=10
```

**Response (200):**
```json
{
  "results": [
    {
      "id": 1,
      "name": "Pizzería Napolitana",
      "address": "Calle Roma 145, Centro Histórico",
      "phone": "555-0101",
      "created_at": "2024-01-15T10:30:45+00:00",
      "updated_at": "2024-01-15T10:30:45+00:00"
    }
  ],
  "pagination": {
    "total": 25,
    "page": 1,
    "limit": 10,
    "pages": 3
  },
  "filters_applied": {
    "search": "pizza",
    "order_by": "created_at",
    "order_direction": "DESC"
  }
}
```

#### GET `/api/restaurants/{id}/similar` 🔒
Encontrar restaurantes similares basado en nombre y dirección.

**Query Parameters:**
- `limit`: Número máximo de resultados similares (max 20, default 5)

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Response (200):**
```json
{
  "reference_restaurant": {
    "id": 1,
    "name": "Pizzería Napolitana",
    "address": "Calle Roma 145, Centro Histórico",
    "phone": "555-0101",
    "created_at": "2024-01-15T10:30:45+00:00",
    "updated_at": "2024-01-15T10:30:45+00:00"
  },
  "similar_restaurants": [
    {
      "id": 5,
      "name": "Pizzería Romana",
      "address": "Avenida Centro 890",
      "phone": "555-0505",
      "created_at": "2024-01-16T14:20:30+00:00",
      "updated_at": "2024-01-16T14:20:30+00:00"
    }
  ],
  "count": 1
}
```

#### GET `/api/restaurants/statistics` 🔒
Obtener estadísticas generales sobre los restaurantes.

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Response (200):**
```json
{
  "total": 150,
  "created_today": 3,
  "created_this_week": 12,
  "created_this_month": 45,
  "average_per_day": 1.5,
  "generated_at": "2024-01-15T10:30:45Z"
}
```

#### GET `/api/restaurants/quick-search` 🔒
Búsqueda rápida para autocompletado (sin paginación).

**Query Parameters:**
- `q`: Término de búsqueda rápida (mínimo 2 caracteres)
- `limit`: Número máximo de resultados (max 50, default 10)

**Headers:**
```
X-API-KEY: your_api_key_here
```

**Ejemplo de uso:**
```bash
GET /api/restaurants/quick-search?q=pizza&limit=5
```

**Response (200):**
```json
{
  "results": [
    {
      "id": 1,
      "name": "Pizzería Napolitana",
      "address": "Calle Roma 145",
      "phone": "555-0101"
    },
    {
      "id": 5,
      "name": "Pizza Palace",
      "address": "Avenida Central 890",
      "phone": "555-0505"
    }
  ],
  "count": 2,
  "query": "pizza"
}
```

---

### 👥 Endpoints de Usuarios

#### GET `/api/users` 🔒
Listar todos los usuarios (solo para administradores).

#### POST `/api/users` 🔒
Crear un nuevo usuario (alternativa a /api/auth/register).

#### GET `/api/users/{id}` 🔒
Obtener un usuario específico.

---

## 🔐 Autenticación y Seguridad

### Métodos de Autenticación

1. **API Key en Header:**
   ```
   X-API-KEY: your_api_key_here
   ```

2. **Bearer Token:**
   ```
   Authorization: Bearer your_api_key_here
   ```

3. **Cookies HttpOnly:** (automático después del login)

### Roles y Permisos

- **ROLE_USER**: Acceso completo a endpoints de restaurantes
- **ROLE_ADMIN**: Acceso adicional a gestión de usuarios

### Generación de API Keys

Las API Keys se generan automáticamente usando:
```php
bin2hex(random_bytes(32))  // 64 caracteres hexadecimales
```

### Configuración de Seguridad

```yaml
# config/packages/security.yaml
security:
    firewalls:
        auth:
            pattern: ^/api/auth
            security: false
        api:
            pattern: ^/api
            custom_authenticators:
                - App\Security\ApiKeyAuthenticator
            user_checker: App\Security\UserChecker
```

---

## 📚 Documentación de la API

### Acceso a la Documentación

1. **Swagger UI:** http://localhost:8080/api/docs
2. **OpenAPI JSON:** http://localhost:8080/api/docs.json
3. **OpenAPI YAML:** http://localhost:8080/api/docs.yaml

### Configuración de Swagger UI

La documentación incluye:
- Esquemas de autenticación configurados
- Ejemplos de request/response
- Validaciones documentadas
- Botón "Authorize" para testing
- **Sección "Restaurant Search"** con endpoints de búsqueda avanzada
- **Sección "Authentication"** con endpoints de autenticación

---

## 🧪 Testing y Uso

### Usuario de Prueba

**Email:** `usuario@ejemplo.com`  
**API Key:** `58437522a95dd2c7be83c4a87d172f9fe680f5aefae082345747f9bfbc68a52c`

### Ejemplos con cURL

#### Operaciones CRUD Básicas

```bash
# Login y obtener API Key
curl -X POST http://localhost:8080/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"usuario@ejemplo.com"}'

# Listar restaurantes
curl -H "X-API-KEY: your_api_key" \
     http://localhost:8080/api/restaurants

# Crear restaurante
curl -X POST http://localhost:8080/api/restaurants \
     -H "Content-Type: application/json" \
     -H "X-API-KEY: your_api_key" \
     -d '{"name":"Mi Restaurante","address":"Mi Dirección","phone":"123456789"}'

# Actualizar restaurante
curl -X PUT http://localhost:8080/api/restaurants/1 \
     -H "Content-Type: application/json" \
     -H "X-API-KEY: your_api_key" \
     -d '{"name":"Restaurante Actualizado","address":"Nueva Dirección","phone":"987654321"}'

# Eliminar restaurante
curl -X DELETE http://localhost:8080/api/restaurants/1 \
     -H "X-API-KEY: your_api_key"
```

#### Búsqueda Avanzada

```bash
# Búsqueda general por texto
curl -H "X-API-KEY: your_api_key" \
     "http://localhost:8080/api/restaurants/search?search=pizza"

# Búsqueda con filtros específicos
curl -H "X-API-KEY: your_api_key" \
     "http://localhost:8080/api/restaurants/search?name=pizz&address=centro&order_by=created_at&order_direction=DESC"

# Búsqueda por rango de fechas
curl -H "X-API-KEY: your_api_key" \
     "http://localhost:8080/api/restaurants/search?created_from=2024-01-01&created_to=2024-01-31"

# Búsqueda rápida para autocompletado
curl -H "X-API-KEY: your_api_key" \
     "http://localhost:8080/api/restaurants/quick-search?q=burger&limit=5"

# Restaurantes similares
curl -H "X-API-KEY: your_api_key" \
     "http://localhost:8080/api/restaurants/1/similar?limit=3"

# Estadísticas del sistema
curl -H "X-API-KEY: your_api_key" \
     "http://localhost:8080/api/restaurants/statistics"
```

#### Verificar Rate Limiting

```bash
# Hacer múltiples requests para ver headers de rate limiting
curl -H "X-API-KEY: your_api_key" \
     http://localhost:8080/api/restaurants -v

# Los headers mostrarán:
# X-RateLimit-Limit: 200
# X-RateLimit-Remaining: 199
# X-RateLimit-Type: authenticated_user
# X-RateLimit-Policy: 200 requests per hour
```

### Testing con Postman

1. Importar la especificación OpenAPI desde: `http://localhost:8080/api/docs.json`
2. Configurar la autenticación en la colección
3. Usar variables de entorno para la API Key
4. Probar los nuevos endpoints de búsqueda avanzada

---

## 🛠️ Comandos Útiles

### Symfony

```bash
# Limpiar cache
docker exec restaurant_api_php php bin/console cache:clear

# Ver rutas disponibles
docker exec restaurant_api_php php bin/console debug:router

# Ver rutas de búsqueda específicamente
docker exec restaurant_api_php php bin/console debug:router | grep search

# Crear migración
docker exec restaurant_api_php php bin/console make:migration

# Ejecutar migraciones
docker exec restaurant_api_php php bin/console doctrine:migrations:migrate

# Crear entidad
docker exec restaurant_api_php php bin/console make:entity

# Ver servicios
docker exec restaurant_api_php php bin/console debug:container

# Ejecutar consulta SQL
docker exec restaurant_api_php php bin/console doctrine:query:sql "SELECT * FROM user"
```

### Docker

```bash
# Ver contenedores activos
docker-compose ps

# Ver logs de un servicio específico
docker-compose logs php
docker-compose logs nginx
docker-compose logs database

# Reiniciar un servicio
docker-compose restart php

# Reconstruir imágenes
docker-compose build --no-cache

# Acceder a bash del contenedor
docker exec -it restaurant_api_php sh
```

### Base de Datos

```bash
# Crear base de datos
docker exec restaurant_api_php php bin/console doctrine:database:create

# Eliminar base de datos
docker exec restaurant_api_php php bin/console doctrine:database:drop --force

# Validar esquema
docker exec restaurant_api_php php bin/console doctrine:schema:validate

# Actualizar esquema
docker exec restaurant_api_php php bin/console doctrine:schema:update --force
```

---

## 🚨 Solución de Problemas

### Problemas Comunes

#### 1. Puerto 8080 ocupado
```bash
# Verificar qué usa el puerto
netstat -ano | findstr :8080  # Windows
lsof -i :8080                 # Linux/Mac

# Cambiar puerto en docker-compose.yml
ports:
  - "8081:80"  # Usar puerto 8081 en lugar de 8080
```

#### 2. Error de permisos en contenedor
```bash
# Arreglar permisos
docker exec restaurant_api_php chown -R www-data:www-data /var/www/html/var
```

#### 3. Error de cache
```bash
# Limpiar cache manualmente
docker exec restaurant_api_php rm -rf var/cache/dev
docker exec restaurant_api_php php bin/console cache:clear
```

#### 4. Base de datos no conecta
```bash
# Verificar que MySQL esté corriendo
docker-compose ps

# Ver logs de MySQL
docker-compose logs database

# Verificar configuración DATABASE_URL en .env
```

#### 5. Swagger UI no carga
```bash
# Instalar dependencias faltantes
docker exec restaurant_api_php composer require symfony/twig-bundle
docker exec restaurant_api_php composer require symfony/asset

# Limpiar cache
docker exec restaurant_api_php php bin/console cache:clear
```

#### 6. Rate Limiting muy restrictivo
```bash
# Verificar configuración en config/packages/rate_limiter.yaml
# Ajustar límites según necesidades

# Ver estado actual del rate limiting
curl -H "X-API-KEY: your_api_key" http://localhost:8080/api/restaurants -v
```

### Logs y Debugging

```bash
# Ver logs de Symfony
docker exec restaurant_api_php tail -f var/log/dev.log

# Ver logs de Nginx
docker exec restaurant_api_nginx tail -f /var/log/nginx/error.log

# Habilitar debug en Symfony
# En .env: APP_ENV=dev
# En .env: APP_DEBUG=true
```

---

## 📈 Mejoras Futuras

### Funcionalidades Completadas ✅

- ✅ **Rate Limiting Inteligente** por usuario/IP con límites diferenciados
- ✅ **Búsqueda Avanzada** con múltiples filtros y ordenamiento
- ✅ **Filtros por fechas** de creación y actualización
- ✅ **Búsqueda rápida** para autocompletado
- ✅ **Restaurantes similares** basado en nombre/dirección
- ✅ **Estadísticas** del sistema
- ✅ **Headers informativos** de rate limiting

### Funcionalidades Pendientes

- [ ] **Soft Delete** para restaurantes
- [ ] **Auditoría** de cambios con historial
- [ ] **Cache** con Redis para mejor rendimiento
- [ ] **Tests Automatizados** (PHPUnit)
- [ ] **CI/CD Pipeline** con GitHub Actions
- [ ] **Métricas** y monitoring con Prometheus
- [ ] **Backup** automatizado de BD
- [ ] **Notificaciones** por email/webhook
- [ ] **Geolocalización** de restaurantes
- [ ] **Categorías** y tags para restaurantes

### Optimizaciones

- [ ] **Índices** optimizados en base de datos
- [ ] **Lazy Loading** en relaciones
- [ ] **Compresión** de respuestas
- [ ] **CDN** para assets estáticos
- [ ] **Load Balancing** para alta disponibilidad
- [ ] **Elasticsearch** para búsquedas complejas
- [ ] **GraphQL** como alternativa a REST

---

## 🤝 Contribución

### Cómo Contribuir

1. Fork el proyecto
2. Crear una rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit los cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abrir un Pull Request

### Estándares de Código

- Seguir **PSR-12** para PHP
- Usar **PHPDoc** para documentación
- Validar con **PHP CS Fixer**
- Tests unitarios para nuevas funcionalidades

---

## 📄 Licencia

Este proyecto está bajo la licencia **MIT**. Ver archivo `LICENSE` para más detalles.

---

## 📞 Contacto y Soporte

- **Email:** support@restaurantapi.local
- **Documentación:** http://localhost:8080/api/docs
- **Issues:** [GitHub Issues](https://github.com/tu-usuario/restaurant-api/issues)

---

## 🎉 ¡Gracias!

Gracias por revisar este proyecto. Si tienes preguntas o sugerencias, no dudes en contactar o abrir un issue.

**¡Happy Coding!** 🚀 