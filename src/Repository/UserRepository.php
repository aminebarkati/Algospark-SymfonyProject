<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /** @return User[] */
    public function findAllOrderedByRating(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.rating', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deductPointsById(int $userId, int $points): void
    {
        $user = $this->find($userId);
        if (!$user) {
            return;
        }

        $user->setRating(max(0, $user->getRating() - abs($points)))->touchUpdatedAt();
        $this->getEntityManager()->flush();
    }

    /** @return User[] */
    public function findFavouritesById(int $userId): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT u2
             FROM App\\Entity\\UserFavorite f
             JOIN f.favoriteUser u2
             WHERE IDENTITY(f.user) = :userId'
        )->setParameter('userId', $userId)->getResult();
    }
}
