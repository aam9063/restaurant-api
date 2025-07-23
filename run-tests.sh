#!/bin/bash

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Banner
echo -e "${BLUE}"
echo "╔═══════════════════════════════════════════════════════════════════╗"
echo "║                    🧪 RESTAURANT API TESTS                        ║"
echo "║                      PHPUnit Test Runner                          ║"
echo "╚═══════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Función para mostrar ayuda
show_help() {
    echo -e "${YELLOW}Uso: $0 [OPCIÓN]${NC}"
    echo ""
    echo "Opciones disponibles:"
    echo "  all               Ejecutar todos los tests"
    echo "  unit              Ejecutar solo tests unitarios"
    echo "  integration       Ejecutar solo tests de integración"
    echo "  functional        Ejecutar solo tests funcionales"
    echo "  coverage          Ejecutar tests con coverage (requiere xdebug)"
    echo "  coverage-html     Generar reporte HTML de coverage"
    echo "  entity            Ejecutar tests de entidades"
    echo "  security          Ejecutar tests de seguridad"
    echo "  repository        Ejecutar tests de repositorios"
    echo "  controller        Ejecutar tests de controladores"
    echo "  fast              Ejecutar tests rápidos (solo unitarios)"
    echo "  debug             Ejecutar con debug verbose"
    echo "  help              Mostrar esta ayuda"
    echo ""
    echo -e "${BLUE}Ejemplos:${NC}"
    echo "  $0 all                    # Todos los tests"
    echo "  $0 unit                   # Solo unitarios"
    echo "  $0 coverage-html          # Con coverage HTML"
    echo "  $0 debug                  # Con debug detallado"
}

# Función para ejecutar comando con feedback
run_command() {
    local cmd="$1"
    local description="$2"
    
    echo -e "${YELLOW}➤ $description${NC}"
    echo -e "${BLUE}Comando: $cmd${NC}"
    echo ""
    
    if eval $cmd; then
        echo -e "${GREEN}✅ $description - COMPLETADO${NC}"
        return 0
    else
        echo -e "${RED}❌ $description - FALLÓ${NC}"
        return 1
    fi
}

# Verificar que Docker esté ejecutándose
check_docker() {
    if ! docker exec restaurant_api_php php -v > /dev/null 2>&1; then
        echo -e "${RED}❌ Error: No se puede conectar al contenedor restaurant_api_php${NC}"
        echo -e "${YELLOW}Asegúrate de que Docker esté ejecutándose y los contenedores estén activos:${NC}"
        echo "  docker-compose up -d"
        exit 1
    fi
}

# Limpiar cache antes de tests
clear_cache() {
    echo -e "${YELLOW}🧹 Limpiando cache...${NC}"
    docker exec restaurant_api_php php bin/console cache:clear --env=test > /dev/null 2>&1
}

# Verificar configuración de base de datos para tests
check_test_config() {
    echo -e "${YELLOW}🔧 Verificando configuración de tests...${NC}"
    if docker exec restaurant_api_php php -r "echo getenv('APP_ENV') ?: 'not set';" | grep -q "test"; then
        echo -e "${GREEN}✅ Entorno de test configurado correctamente${NC}"
    else
        echo -e "${YELLOW}⚠️  Configurando entorno de test...${NC}"
    fi
}

# Función principal
main() {
    local option=${1:-"help"}
    
    # Verificaciones iniciales
    check_docker
    clear_cache
    check_test_config
    
    echo ""
    
    case $option in
        "all")
            run_command "docker exec restaurant_api_php php bin/phpunit --testdox" \
                       "Ejecutando TODOS los tests"
            ;;
            
        "unit")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Unit --testdox" \
                       "Ejecutando tests UNITARIOS"
            ;;
            
        "integration")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Integration --testdox" \
                       "Ejecutando tests de INTEGRACIÓN"
            ;;
            
        "functional")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Functional --testdox" \
                       "Ejecutando tests FUNCIONALES"
            ;;
            
        "coverage")
            run_command "docker exec restaurant_api_php php bin/phpunit --coverage-text" \
                       "Ejecutando tests con COVERAGE (texto)"
            ;;
            
        "coverage-html")
            echo -e "${YELLOW}📊 Generando reporte HTML de coverage...${NC}"
            run_command "docker exec restaurant_api_php php bin/phpunit --coverage-html var/coverage" \
                       "Generando reporte HTML de coverage"
            echo -e "${GREEN}📄 Reporte disponible en: var/coverage/index.html${NC}"
            ;;
            
        "entity")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Unit/Entity --testdox" \
                       "Ejecutando tests de ENTIDADES"
            ;;
            
        "security")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Unit/Security --testdox" \
                       "Ejecutando tests de SEGURIDAD"
            ;;
            
        "repository")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Integration/Repository --testdox" \
                       "Ejecutando tests de REPOSITORIOS"
            ;;
            
        "controller")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Functional/Controller --testdox" \
                       "Ejecutando tests de CONTROLADORES"
            ;;
            
        "fast")
            run_command "docker exec restaurant_api_php php bin/phpunit tests/Unit --testdox --stop-on-failure" \
                       "Ejecutando tests RÁPIDOS (solo unitarios)"
            ;;
            
        "debug")
            run_command "docker exec restaurant_api_php php bin/phpunit --verbose --debug" \
                       "Ejecutando tests con DEBUG detallado"
            ;;
            
        "help"|"-h"|"--help")
            show_help
            ;;
            
        *)
            echo -e "${RED}❌ Opción no válida: $option${NC}"
            echo ""
            show_help
            exit 1
            ;;
    esac
    
    # Mostrar estadísticas finales
    if [ $? -eq 0 ] && [ "$option" != "help" ]; then
        echo ""
        echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║                         ✅ TESTS COMPLETADOS                      ║${NC}"
        echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════════╝${NC}"
        
        # Mostrar información adicional
        echo -e "${BLUE}📊 Información adicional:${NC}"
        echo "• Logs disponibles en: var/log/"
        echo "• Cache de tests en: var/cache/test/"
        echo "• Para más opciones: $0 help"
    fi
}

# Ejecutar función principal con todos los argumentos
main "$@" 