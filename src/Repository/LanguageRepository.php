<?php
namespace App\Repository;

use App\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Language::class);
    }

    /** @return Language[] */
    public function findEnabledLanguages(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.isEnabled = true')
            ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
