<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_favorites')]
class UserFavorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'favorite_user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $favoriteUser;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getFavoriteUser(): User { return $this->favoriteUser; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function setFavoriteUser(User $favoriteUser): self { $this->favoriteUser = $favoriteUser; return $this; }
}
