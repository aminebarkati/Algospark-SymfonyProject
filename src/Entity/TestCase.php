<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TestCaseRepository::class)]
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

    public function setProblem(Problem $problem): self
    {
        $this->problem = $problem;
        return $this;
    }

    public function setInput(string $input): self
    {
        $this->input = $input;
        return $this;
    }

    public function setExpectedOutput(string $expectedOutput): self
    {
        $this->expectedOutput = $expectedOutput;
        return $this;
    }

    public function setSample(bool $isSample): self
    {
        $this->isSample = $isSample;
        return $this;
    }
}
