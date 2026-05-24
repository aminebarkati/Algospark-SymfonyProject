<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'submissions')]
class Submission
{
    public function __construct()
    {
        $this->submittedAt = new \DateTimeImmutable();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Problem::class)]
    #[ORM\JoinColumn(name: 'problem_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Problem $problem;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id')]
    private Language $language;

    #[ORM\ManyToOne(targetEntity: VerdictStatus::class)]
    #[ORM\JoinColumn(name: 'verdict_id', referencedColumnName: 'id')]
    private ?VerdictStatus $verdict = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $executionTimeMs = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $memoryUsedMb = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime', name: 'submitted_at')]
    private \DateTimeInterface $submittedAt;

    #[ORM\Column(type: 'datetime', name: 'judged_at', nullable: true)]
    private ?\DateTimeInterface $judgedAt = null;

    #[ORM\Column(type: 'integer', name: 'passed_tests', options: ['default' => 0])]
    private int $passedTests = 0;

    #[ORM\Column(type: 'integer', name: 'total_tests', nullable: true)]
    private ?int $totalTests = null;

    public function getId(): ?int { return $this->id; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function setProblem(Problem $problem): self { $this->problem = $problem; return $this; }
    public function setLanguage(Language $language): self { $this->language = $language; return $this; }
    public function setVerdict(?VerdictStatus $verdict): self { $this->verdict = $verdict; return $this; }
    public function setExecutionTimeMs(?int $executionTimeMs): self { $this->executionTimeMs = $executionTimeMs; return $this; }
    public function setMemoryUsedMb(?int $memoryUsedMb): self { $this->memoryUsedMb = $memoryUsedMb; return $this; }
    public function setErrorMessage(?string $errorMessage): self { $this->errorMessage = $errorMessage; return $this; }
    public function setPassedTests(int $passedTests): self { $this->passedTests = $passedTests; return $this; }
    public function setTotalTests(?int $totalTests): self { $this->totalTests = $totalTests; return $this; }
    public function setSubmittedAt(\DateTimeInterface $submittedAt): self { $this->submittedAt = $submittedAt; return $this; }
    public function setJudgedAt(?\DateTimeInterface $judgedAt): self { $this->judgedAt = $judgedAt; return $this; }
    public function getUser(): User { return $this->user; }
    public function getProblem(): Problem { return $this->problem; }
    public function getLanguage(): Language { return $this->language; }
    public function getVerdict(): ?VerdictStatus { return $this->verdict; }
    public function getExecutionTimeMs(): ?int { return $this->executionTimeMs; }
    public function getMemoryUsedMb(): ?int { return $this->memoryUsedMb; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function getSubmittedAt(): \DateTimeInterface { return $this->submittedAt; }
    public function getJudgedAt(): ?\DateTimeInterface { return $this->judgedAt; }
    public function getPassedTests(): int { return $this->passedTests; }
    public function getTotalTests(): ?int { return $this->totalTests; }
}
