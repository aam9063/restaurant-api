<?php

namespace App\Users\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserProviderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Buscar usuario por API Key.
     */
    public function findByApiKey(string $apiKey): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.apiKey = :apiKey')
            ->andWhere('u.isActive = :active')
            ->setParameter('apiKey', $apiKey)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Buscar usuario por email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.isActive = :active')
            ->setParameter('email', $email)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Implementación requerida por UserProviderInterface.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return $this->find($user->getId()) ?? throw new UnsupportedUserException('User not found.');
    }

    /**
     * Implementación requerida por UserProviderInterface.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Implementación requerida por UserProviderInterface.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->findByEmail($identifier);

        if (!$user) {
            throw new UnsupportedUserException(sprintf('User with email "%s" not found.', $identifier));
        }

        return $user;
    }

    /**
     * Contar usuarios activos.
     */
    public function countActiveUsers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
