<?php
namespace App\Repository;

use App\Entity\Submission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Submission::class);
    }

    /** @return Submission[] */
    public function findLatest(int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')->addSelect('u')
            ->join('s.problem', 'p')->addSelect('p')
            ->join('s.language', 'l')->addSelect('l')
            ->leftJoin('s.verdict', 'v')->addSelect('v')
            ->orderBy('s.submittedAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('IDENTITY(s.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countSolvedProblemsByUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(DISTINCT p.id)')
            ->join('s.problem', 'p')
            ->join('s.verdict', 'v')
            ->andWhere('IDENTITY(s.user) = :userId')
            ->andWhere('v.verdict = :acceptedVerdict')
            ->setParameter('userId', $userId)
            ->setParameter('acceptedVerdict', 'AC')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Submission[] */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')->addSelect('u')
            ->join('s.problem', 'p')->addSelect('p')
            ->join('s.language', 'l')->addSelect('l')
            ->leftJoin('s.verdict', 'v')->addSelect('v')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Submission[] */
    public function findAllFavoritesByUserId(int $userId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')->addSelect('u')
            ->join('s.problem', 'p')->addSelect('p')
            ->join('s.language', 'l')->addSelect('l')
            ->leftJoin('s.verdict', 'v')->addSelect('v')
            ->innerJoin('App\\Entity\\UserFavorite', 'f', 'WITH', 'f.favoriteUser = s.user AND IDENTITY(f.user) = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<int, array<string, mixed>> */
    public function findRecentByUserAndProblem(int $userId, int $problemId, int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('s')
            ->join('s.language', 'l')->addSelect('l')
            ->leftJoin('s.verdict', 'v')->addSelect('v')
            ->andWhere('IDENTITY(s.user) = :userId')
            ->andWhere('IDENTITY(s.problem) = :problemId')
            ->setParameter('userId', $userId)
            ->setParameter('problemId', $problemId)
            ->orderBy('s.submittedAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults(max(1, min($limit, 20)))
            ->getQuery()
            ->getResult();

        return array_map(static function (Submission $submission): array {
            $verdict = $submission->getVerdict();
            return [
                'id' => $submission->getId(),
                'submitted_at' => $submission->getSubmittedAt()->format(DATE_ATOM),
                'language_name' => $submission->getLanguage()->getName(),
                'verdict' => $verdict?->getVerdict() ?? 'PENDING',
                'display_name' => $verdict?->getDisplayName() ?? 'Pending',
                'color_code' => $verdict?->getColorCode() ?? '#6c757d',
                'execution_time_ms' => $submission->getExecutionTimeMs(),
                'memory_used_mb' => $submission->getMemoryUsedMb(),
            ];
        }, $rows);
    }

    public function createPending(int $userId, int $problemId, int $languageId): Submission
    {
        $submission = new Submission();
        $em = $this->getEntityManager();
        $user = $em->getReference('App\\Entity\\User', $userId);
        $problem = $em->getReference('App\\Entity\\Problem', $problemId);
        $language = $em->getReference('App\\Entity\\Language', $languageId);

        $submission->setUser($user)->setProblem($problem)->setLanguage($language);
        $em->persist($submission);
        $em->flush();

        return $submission;
    }
}
