<?php

namespace App\Repository;

use App\Entity\Restaurant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    /**
     * Buscar restaurantes por nombre
     */
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Buscar restaurantes por ciudad en la dirección
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.address LIKE :city')
            ->setParameter('city', '%' . $city . '%')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Contar total de restaurantes
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Búsqueda avanzada con múltiples criterios
     */
    public function findByAdvancedSearch(array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('r');
        
        // Filtro por texto general (busca en nombre, dirección y teléfono)
        if (!empty($criteria['search'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('r.name', ':search'),
                    $qb->expr()->like('r.address', ':search'),
                    $qb->expr()->like('r.phone', ':search')
                )
            )->setParameter('search', '%' . $criteria['search'] . '%');
        }
        
        // Filtro específico por nombre
        if (!empty($criteria['name'])) {
            $qb->andWhere('r.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }
        
        // Filtro específico por dirección
        if (!empty($criteria['address'])) {
            $qb->andWhere('r.address LIKE :address')
               ->setParameter('address', '%' . $criteria['address'] . '%');
        }
        
        // Filtro específico por teléfono
        if (!empty($criteria['phone'])) {
            $qb->andWhere('r.phone LIKE :phone')
               ->setParameter('phone', '%' . $criteria['phone'] . '%');
        }
        
        // Filtro por fecha de creación (desde)
        if (!empty($criteria['created_from'])) {
            $qb->andWhere('r.createdAt >= :created_from')
               ->setParameter('created_from', new \DateTimeImmutable($criteria['created_from']));
        }
        
        // Filtro por fecha de creación (hasta)
        if (!empty($criteria['created_to'])) {
            $qb->andWhere('r.createdAt <= :created_to')
               ->setParameter('created_to', new \DateTimeImmutable($criteria['created_to'] . ' 23:59:59'));
        }
        
        // Filtro por fecha de actualización (desde)
        if (!empty($criteria['updated_from'])) {
            $qb->andWhere('r.updatedAt >= :updated_from')
               ->setParameter('updated_from', new \DateTimeImmutable($criteria['updated_from']));
        }
        
        // Filtro por fecha de actualización (hasta)
        if (!empty($criteria['updated_to'])) {
            $qb->andWhere('r.updatedAt <= :updated_to')
               ->setParameter('updated_to', new \DateTimeImmutable($criteria['updated_to'] . ' 23:59:59'));
        }
        
        // Aplicar ordenamiento
        $this->applyOrderBy($qb, $criteria);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Búsqueda avanzada con paginación
     */
    public function findByAdvancedSearchWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('r');
        
        // Aplicar los mismos filtros que en findByAdvancedSearch
        $this->applyFilters($qb, $criteria);
        
        // Aplicar ordenamiento
        $this->applyOrderBy($qb, $criteria);
        
        // Calcular offset
        $offset = ($page - 1) * $limit;
        
        // Aplicar paginación
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);
        
        $results = $qb->getQuery()->getResult();
        
        // Contar total de resultados para paginación
        $totalQb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)');
        $this->applyFilters($totalQb, $criteria);
        $total = $totalQb->getQuery()->getSingleScalarResult();
        
        return [
            'results' => $results,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Aplicar filtros a un QueryBuilder
     */
    private function applyFilters(QueryBuilder $qb, array $criteria): void
    {
        // Filtro por texto general
        if (!empty($criteria['search'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('r.name', ':search'),
                    $qb->expr()->like('r.address', ':search'),
                    $qb->expr()->like('r.phone', ':search')
                )
            )->setParameter('search', '%' . $criteria['search'] . '%');
        }
        
        // Filtros específicos
        if (!empty($criteria['name'])) {
            $qb->andWhere('r.name LIKE :name')
               ->setParameter('name', '%' . $criteria['name'] . '%');
        }
        
        if (!empty($criteria['address'])) {
            $qb->andWhere('r.address LIKE :address')
               ->setParameter('address', '%' . $criteria['address'] . '%');
        }
        
        if (!empty($criteria['phone'])) {
            $qb->andWhere('r.phone LIKE :phone')
               ->setParameter('phone', '%' . $criteria['phone'] . '%');
        }
        
        // Filtros por fechas
        if (!empty($criteria['created_from'])) {
            $qb->andWhere('r.createdAt >= :created_from')
               ->setParameter('created_from', new \DateTimeImmutable($criteria['created_from']));
        }
        
        if (!empty($criteria['created_to'])) {
            $qb->andWhere('r.createdAt <= :created_to')
               ->setParameter('created_to', new \DateTimeImmutable($criteria['created_to'] . ' 23:59:59'));
        }
        
        if (!empty($criteria['updated_from'])) {
            $qb->andWhere('r.updatedAt >= :updated_from')
               ->setParameter('updated_from', new \DateTimeImmutable($criteria['updated_from']));
        }
        
        if (!empty($criteria['updated_to'])) {
            $qb->andWhere('r.updatedAt <= :updated_to')
               ->setParameter('updated_to', new \DateTimeImmutable($criteria['updated_to'] . ' 23:59:59'));
        }
    }

    /**
     * Aplicar ordenamiento a un QueryBuilder
     */
    private function applyOrderBy(QueryBuilder $qb, array $criteria): void
    {
        $orderBy = $criteria['order_by'] ?? 'name';
        $direction = strtoupper($criteria['order_direction'] ?? 'ASC');
        
        // Validar dirección
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        
        // Aplicar ordenamiento según el campo
        switch ($orderBy) {
            case 'name':
                $qb->orderBy('r.name', $direction);
                break;
            case 'address':
                $qb->orderBy('r.address', $direction);
                break;
            case 'phone':
                $qb->orderBy('r.phone', $direction);
                break;
            case 'created_at':
            case 'createdAt':
                $qb->orderBy('r.createdAt', $direction);
                break;
            case 'updated_at':
            case 'updatedAt':
                $qb->orderBy('r.updatedAt', $direction);
                break;
            default:
                $qb->orderBy('r.name', 'ASC');
        }
        
        // Agregar ordenamiento secundario por ID para consistencia
        if ($orderBy !== 'id') {
            $qb->addOrderBy('r.id', 'ASC');
        }
    }

    /**
     * Buscar restaurantes similares (por nombre o dirección)
     */
    public function findSimilar(Restaurant $restaurant, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.id != :id')
            ->andWhere(
                $this->createQueryBuilder('r')->expr()->orX(
                    'r.name LIKE :name',
                    'r.address LIKE :address'
                )
            )
            ->setParameter('id', $restaurant->getId())
            ->setParameter('name', '%' . $restaurant->getName() . '%')
            ->setParameter('address', '%' . $restaurant->getAddress() . '%')
            ->setMaxResults($limit)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtener estadísticas de restaurantes
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('r');
        
        $total = $qb->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $today = new \DateTimeImmutable('today');
        $thisWeek = new \DateTimeImmutable('monday this week');
        $thisMonth = new \DateTimeImmutable('first day of this month');
        
        $createdToday = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
        
        $createdThisWeek = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :thisWeek')
            ->setParameter('thisWeek', $thisWeek)
            ->getQuery()
            ->getSingleScalarResult();
        
        $createdThisMonth = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :thisMonth')
            ->setParameter('thisMonth', $thisMonth)
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'total' => $total,
            'created_today' => $createdToday,
            'created_this_week' => $createdThisWeek,
            'created_this_month' => $createdThisMonth,
            'average_per_day' => $thisMonth->diff(new \DateTimeImmutable())->days > 0 
                ? round($createdThisMonth / $thisMonth->diff(new \DateTimeImmutable())->days, 2) 
                : 0
        ];
    }
} 