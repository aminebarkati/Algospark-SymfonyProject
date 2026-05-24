<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'problems')]
class Problem
{
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 200, unique: true)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'integer')]
    private int $difficulty = 900;

    #[ORM\Column(type: 'string', length: 300)]
    private string $category = 'General';

    #[ORM\Column(type: 'integer')]
    private int $timeLimitMs = 1000;

    #[ORM\Column(type: 'integer')]
    private int $memoryLimitMb = 256;

    #[ORM\Column(type: 'integer')]
    private int $successCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalAttempts = 0;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $acceptanceRate = '0.00';

    #[ORM\Column(type: 'datetime', name: 'created_at', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at', nullable: true, options: ['default' => null])]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }

    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function setDifficulty(int $difficulty): self { $this->difficulty = $difficulty; return $this; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }
    public function setTimeLimitMs(int $timeLimitMs): self { $this->timeLimitMs = $timeLimitMs; return $this; }
    public function setMemoryLimitMb(int $memoryLimitMb): self { $this->memoryLimitMb = $memoryLimitMb; return $this; }
    public function setSuccessCount(int $successCount): self { $this->successCount = $successCount; return $this; }
    public function setTotalAttempts(int $totalAttempts): self { $this->totalAttempts = $totalAttempts; return $this; }
    public function setAcceptanceRate(float|int|string $acceptanceRate): self
    {
        $this->acceptanceRate = number_format((float) $acceptanceRate, 2, '.', '');
        return $this;
    }

    public function getDifficulty(): int { return $this->difficulty; }
    public function getCategory(): string { return $this->category; }
    public function getTimeLimitMs(): int { return $this->timeLimitMs; }
    public function getMemoryLimitMb(): int { return $this->memoryLimitMb; }
    public function getSuccessCount(): int { return $this->successCount; }
    public function getTotalAttempts(): int { return $this->totalAttempts; }
    public function getAcceptanceRate(): float { return (float) $this->acceptanceRate; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}
