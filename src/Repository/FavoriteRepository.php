<?php
namespace App\Repository;

use App\Entity\UserFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFavorite::class);
    }

    public function checkFavoriteById(int $userId, int $favoriteUserId): ?UserFavorite
    {
        return $this->createQueryBuilder('f')
            ->andWhere('IDENTITY(f.user) = :userId')
            ->andWhere('IDENTITY(f.favoriteUser) = :favoriteUserId')
            ->setParameter('userId', $userId)
            ->setParameter('favoriteUserId', $favoriteUserId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteByUserId(int $userId, int $favoriteUserId): void
    {
        $favorite = $this->checkFavoriteById($userId, $favoriteUserId);
        if ($favorite) {
            $this->getEntityManager()->remove($favorite);
            $this->getEntityManager()->flush();
        }
    }

    public function addFavorite(int $userId, int $favoriteUserId): UserFavorite
    {
        $em = $this->getEntityManager();
        $favorite = new UserFavorite();
        $favorite->setUser($em->getReference('App\\Entity\\User', $userId));
        $favorite->setFavoriteUser($em->getReference('App\\Entity\\User', $favoriteUserId));
        $em->persist($favorite);
        $em->flush();
        return $favorite;
    }
}
