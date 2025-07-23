<?php

namespace App\Repository;

use App\Entity\Restaurant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
} 