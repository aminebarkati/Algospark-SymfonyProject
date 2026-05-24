<?php
namespace App\Repository;

use App\Entity\TestCase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TestCaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestCase::class);
    }

    /** @return TestCase[] */
    public function findSampleByProblemId(int $problemId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('IDENTITY(t.problem) = :problemId')
            ->andWhere('t.isSample = true')
            ->setParameter('problemId', $problemId)
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return TestCase[] */
    public function findByProblemId(int $problemId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('IDENTITY(t.problem) = :problemId')
            ->setParameter('problemId', $problemId)
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
