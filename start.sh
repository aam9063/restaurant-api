#!/bin/bash

# Colores para mensajes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}===========================================================${NC}"
echo -e "${BLUE}      Iniciando Restaurant API - Prueba Técnica Backend     ${NC}"
echo -e "${BLUE}===========================================================${NC}"

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Error: Docker no está instalado. Por favor, instale Docker antes de continuar.${NC}"
    exit 1
fi

# Verificar si Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Error: Docker Compose no está instalado. Por favor, instale Docker Compose antes de continuar.${NC}"
    exit 1
fi

# Verificar si los puertos necesarios están disponibles
echo -e "${YELLOW}Verificando disponibilidad de puertos...${NC}"

# Verificar puerto 8080
if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null ; then
    echo -e "${RED}Error: El puerto 8080 ya está en uso. Por favor, libere este puerto o modifique docker-compose.yml.${NC}"
    exit 1
fi

# Verificar puerto 3307
if lsof -Pi :3307 -sTCP:LISTEN -t >/dev/null ; then
    echo -e "${RED}Error: El puerto 3307 ya está en uso. Por favor, libere este puerto o modifique docker-compose.yml.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Puertos disponibles${NC}"

# Crear archivo .env si no existe
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creando archivo .env...${NC}"
    cat > .env << EOF
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
EOF
    echo -e "${GREEN}✓ Archivo .env creado${NC}"
else
    echo -e "${GREEN}✓ Archivo .env ya existe${NC}"
fi

# Construir y levantar contenedores Docker
echo -e "${YELLOW}Construyendo y levantando contenedores Docker...${NC}"
docker-compose up -d --build

# Esperar a que MySQL esté listo
echo -e "${YELLOW}Esperando a que MySQL esté listo...${NC}"
sleep 10

# Verificar si MySQL está listo
MAX_TRIES=30
COUNTER=0
while ! docker exec restaurant_api_mysql mysqladmin ping -h"localhost" --silent; do
    echo -e "${YELLOW}Esperando a que MySQL esté disponible... ($COUNTER/$MAX_TRIES)${NC}"
    sleep 2
    COUNTER=$((COUNTER+1))
    if [ $COUNTER -ge $MAX_TRIES ]; then
        echo -e "${RED}Error: MySQL no está disponible después de $MAX_TRIES intentos.${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✓ MySQL está listo${NC}"

# Instalar dependencias de Composer
echo -e "${YELLOW}Instalando dependencias de Composer...${NC}"
docker exec restaurant_api_php composer install

# Ejecutar migraciones de base de datos
echo -e "${YELLOW}Ejecutando migraciones de base de datos...${NC}"
docker exec restaurant_api_php php bin/console doctrine:migrations:migrate -n

# Cargar datos de prueba
echo -e "${YELLOW}Cargando datos de prueba...${NC}"
docker exec restaurant_api_php php bin/console doctrine:fixtures:load -n

# Limpiar cache
echo -e "${YELLOW}Limpiando cache...${NC}"
docker exec restaurant_api_php php bin/console cache:clear

# Configurar permisos
echo -e "${YELLOW}Configurando permisos...${NC}"
docker exec restaurant_api_php chown -R www-data:www-data /var/www/html/var

# Mostrar información de acceso
echo -e "${GREEN}===========================================================${NC}"
echo -e "${GREEN}      ¡Restaurant API iniciado correctamente!              ${NC}"
echo -e "${GREEN}===========================================================${NC}"
echo -e "${BLUE}Acceso a la API:${NC} http://localhost:8080/api"
echo -e "${BLUE}Documentación:${NC} http://localhost:8080/api/docs"
echo -e "${BLUE}Base de datos:${NC} localhost:3307 (usuario: root, contraseña: rootpassword)"
echo -e "${BLUE}Usuario de prueba:${NC} usuario@ejemplo.com"
echo -e "${BLUE}API Key de prueba:${NC} 58437522a95dd2c7be83c4a87d172f9fe680f5aefae082345747f9bfbc68a52c"
echo -e "${GREEN}===========================================================${NC}"
echo -e "${YELLOW}Para detener los servicios:${NC} docker-compose down"
echo -e "${YELLOW}Para ver los logs:${NC} docker-compose logs -f"
echo -e "${GREEN}===========================================================${NC}" 