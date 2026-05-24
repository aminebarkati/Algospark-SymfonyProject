<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'verdict_status')]
class VerdictStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $verdict;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $colorCode = null;

    public function getId(): ?int { return $this->id; }
    public function getVerdict(): string { return $this->verdict; }

    public function setVerdict(string $v): self
    {
        $this->verdict = $v;
        return $this;
    }

    public function setDisplayName(?string $n): self
    {
        $this->displayName = $n;
        return $this;
    }

    public function setColorCode(?string $c): self
    {
        $this->colorCode = $c;
        return $this;
    }

    public function getDisplayName(): ?string { return $this->displayName; }
    public function getColorCode(): ?string { return $this->colorCode; }
}
