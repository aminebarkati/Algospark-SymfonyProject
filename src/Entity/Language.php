<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'languages')]
class Language
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $compilerCommand = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $fileExtension = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isEnabled = true;

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }

    public function isEnabled(): bool { return $this->isEnabled; }

    public function getCompilerCommand(): ?string { return $this->compilerCommand; }
    public function getFileExtension(): ?string { return $this->fileExtension; }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setCompilerCommand(?string $cmd): self
    {
        $this->compilerCommand = $cmd;
        return $this;
    }

    public function setFileExtension(?string $ext): self
    {
        $this->fileExtension = $ext;
        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->isEnabled = $enabled;
        return $this;
    }
}
