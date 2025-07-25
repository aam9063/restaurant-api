#!/bin/bash

# Script para ejecutar el linter PHP-CS-Fixer en el proyecto

# Colores para mensajes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== Ejecutando PHP-CS-Fixer ===${NC}"
echo

# Verificar si estamos en Docker
if [ -f /.dockerenv ] || [ -f /proc/self/cgroup ] && grep -q docker /proc/self/cgroup; then
    # Estamos dentro del contenedor Docker
    COMMAND="php ./vendor/bin/php-cs-fixer"
else
    # Estamos en el host, usar Docker
    COMMAND="docker exec restaurant_api_php php ./vendor/bin/php-cs-fixer"
fi

# Verificar si se pasa el parámetro --fix
if [ "$1" == "--fix" ]; then
    echo -e "${YELLOW}Corrigiendo automáticamente los problemas de estilo de código...${NC}"
    $COMMAND fix --verbose
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}¡Código corregido exitosamente!${NC}"
    else
        echo -e "${RED}Se encontraron errores al corregir el código.${NC}"
        exit $EXIT_CODE
    fi
else
    echo -e "${YELLOW}Verificando estilo de código (modo dry-run)...${NC}"
    $COMMAND fix --dry-run --diff --verbose
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}¡El código cumple con los estándares de estilo!${NC}"
    else
        echo -e "${RED}Se encontraron problemas de estilo de código.${NC}"
        echo -e "${YELLOW}Ejecuta './lint.sh --fix' para corregir automáticamente.${NC}"
        exit $EXIT_CODE
    fi
fi

exit 0 