<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 30, unique: true)]
    private string $username;

    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 300)]
    private string $password;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'integer')]
    private int $rating = 1200;

    #[ORM\Column(type: 'boolean')]
    private bool $isAdmin = false;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->roles = [];
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    // UserInterface compatibility
    public function getRoles(): array
    {
        $roles = isset($this->roles) ? $this->roles : [];
        if ($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $p): self { $this->password = $p; return $this; }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $u): self { $this->username = $u; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $e): self { $this->email = $e; return $this; }
    public function isAdmin(): bool { return $this->isAdmin; }

    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): self { $this->bio = $bio; return $this; }
    public function getAvatarUrl(): ?string { return $this->avatarUrl; }
    public function setAvatarUrl(?string $avatarUrl): self { $this->avatarUrl = $avatarUrl; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): self { $this->rating = $rating; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function touchUpdatedAt(): self { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
