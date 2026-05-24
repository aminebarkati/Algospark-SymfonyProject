<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test_cases')]
class TestCase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Problem::class)]
    #[ORM\JoinColumn(name: 'problem_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Problem $problem;

    #[ORM\Column(type: 'text', name: 'input')]
    private string $input;

    #[ORM\Column(type: 'text', name: 'expected_output')]
    private string $expectedOutput;

    #[ORM\Column(type: 'boolean')]
    private bool $isSample = false;

    public function getId(): ?int { return $this->id; }
    public function getInput(): string { return $this->input; }
    public function getExpectedOutput(): string { return $this->expectedOutput; }
    public function isSample(): bool { return $this->isSample; }
}
