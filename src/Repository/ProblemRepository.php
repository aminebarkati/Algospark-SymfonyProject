<?php
namespace App\Repository;

use App\Entity\Problem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProblemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Problem::class);
    }

    /** @return Problem[] */
    public function findLatest(int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function updateJudgingStats(int $problemId, bool $accepted): void
    {
        $problem = $this->find($problemId);
        if (!$problem) {
            return;
        }

        $successCount = $problem->getSuccessCount() + ($accepted ? 1 : 0);
        $totalAttempts = $problem->getTotalAttempts() + 1;
        $acceptanceRate = $totalAttempts > 0 ? round(($successCount / $totalAttempts) * 100, 2) : 0.0;

        $problem->setSuccessCount($successCount)
            ->setTotalAttempts($totalAttempts)
            ->setAcceptanceRate($acceptanceRate);

        $this->getEntityManager()->flush();
    }
}
