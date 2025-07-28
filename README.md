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

```bash
# Clonar el repositorio
git clone <repository-url>
cd restaurant-api

# Ejecutar script de inicio (configura todo automáticamente)
./start.sh
```

### Configuración de Variables de Entorno

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

## 🧪 Ejecutar Tests

Para ejecutar los tests del proyecto:

```bash
# Ejecutar todos los tests
./run-tests.sh

# O alternativamente
docker exec restaurant_api_php php bin/phpunit
```

---

## 🧹 Linter y Estándares de Código

El proyecto utiliza PHP-CS-Fixer para mantener un estilo de código consistente siguiendo los estándares de Symfony y PSR-12.

### Verificar el estilo de código

```bash
# Usando el script de shell (recomendado)
./lint.sh

# O usando Composer
composer cs-check
```

### Corregir problemas de estilo automáticamente

```bash
# Usando el script de shell (recomendado)
./lint.sh --fix

# O usando Composer
composer cs-fix
```

---

## 📚 Documentación de la API

La documentación completa de la API está disponible en:

- **Swagger UI:** http://localhost:8080/api/docs
- **OpenAPI JSON:** http://localhost:8080/api/docs.json
- **OpenAPI YAML:** http://localhost:8080/api/docs.yaml

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

---

## 🧪 Testing y Uso

### Usuario de Prueba

**Email:** `usuario@ejemplo.com`  
**API Key:** `8d0ec4ee8bc655269a7f25b50c19196de68826b7ce9d250b38535b4b04055e65`

### Ejemplos con cURL

```bash
# Login y obtener API Key
curl -X POST http://localhost:8080/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"usuario@ejemplo.com"}'

# Listar restaurantes
curl -H "X-API-KEY: your_api_key" \
     http://localhost:8080/api/restaurants

# Búsqueda avanzada
curl -H "X-API-KEY: your_api_key" \
     "http://localhost:8080/api/restaurants/search?search=pizza&order_by=created_at&order_direction=DESC"
```

---

## 🚀 Sistema de Caché con Redis

El proyecto utiliza Redis para mejorar el rendimiento mediante caché inteligente:

### Características del Sistema de Caché

- **Caché de búsquedas**: Las búsquedas avanzadas y rápidas se cachean por 15-30 minutos
- **Caché de usuarios**: Los datos de usuarios (API keys, emails) se cachean por 1 hora
- **Caché de estadísticas**: Las estadísticas de restaurantes se cachean por 1 hora
- **Invalidación automática**: La caché se invalida automáticamente cuando los datos cambian
- **Pools separados**: Diferentes tipos de datos usan pools de Redis separados para mejor organización

### Configuración de Redis

El sistema está configurado con:
- **Puerto**: 6379 (accesible desde el host)
- **Persistencia**: Datos persistentes con AOF (Append Only File)
- **Múltiples bases de datos**: 
  - Base 0: Caché general de la aplicación
  - Base 1: Caché de restaurantes
  - Base 2: Caché de usuarios
  - Base 3: Caché de búsquedas

### Beneficios de Rendimiento

- Reducción significativa en consultas a la base de datos
- Respuestas más rápidas para búsquedas frecuentes
- Mejor experiencia de usuario en operaciones repetitivas
- Escalabilidad mejorada para múltiples usuarios concurrentes

---

## 🛠️ Comandos Útiles

### Symfony

```bash
# Limpiar cache
docker exec restaurant_api_php php bin/console cache:clear

# Ver rutas disponibles
docker exec restaurant_api_php php bin/console debug:router

# Ejecutar migraciones
docker exec restaurant_api_php php bin/console doctrine:migrations:migrate
```

### Docker

```bash
# Ver contenedores activos
docker-compose ps

# Ver logs de un servicio específico
docker-compose logs php

# Acceder a bash del contenedor
docker exec -it restaurant_api_php sh
```

### Cache Redis

```bash
# Ver estadísticas de Redis
docker exec restaurant_api_redis redis-cli info

# Limpiar toda la caché de Redis
docker exec restaurant_api_redis redis-cli flushall

# Monitorear comandos en tiempo real
docker exec restaurant_api_redis redis-cli monitor

# Ver claves almacenadas en caché
docker exec restaurant_api_redis redis-cli keys "*"

# Limpiar pools específicos de caché
docker exec restaurant_api_php php bin/console cache:pool:clear cache.restaurants
docker exec restaurant_api_php php bin/console cache:pool:clear cache.users
docker exec restaurant_api_php php bin/console cache:pool:clear cache.searches
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

#### 3. Base de datos no conecta
```bash
# Verificar que MySQL esté corriendo
docker-compose ps

# Ver logs de MySQL
docker-compose logs database
```

#### 4. Redis no funciona
```bash
# Verificar que Redis esté corriendo
docker exec restaurant_api_redis redis-cli ping

# Ver logs de Redis
docker-compose logs redis

# Reiniciar Redis
docker-compose restart redis
```

---


### Funcionalidades Pendientes

- [ ] **Soft Delete** para restaurantes
- [ ] **Auditoría** de cambios con historial
- [x] **Cache** con Redis para mejor rendimiento
- [ ] **CI/CD Pipeline** con GitHub Actions
- [ ] **Geolocalización** de restaurantes
- [ ] **Categorías** y tags para restaurantes

---
