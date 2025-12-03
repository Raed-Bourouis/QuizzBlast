<?php

namespace App\Entity;

use App\Repository\PlayerAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerAnswerRepository::class)]
class PlayerAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $points = null;

    #[ORM\Column]
    private ?int $timeToAnswer = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $answeredAt = null;

    #[ORM\ManyToOne(inversedBy: 'playerAnswers')]
    private ?GameParticipant $gameParticipant = null;

    #[ORM\ManyToOne]
    private ?Question $question = null;

    #[ORM\ManyToOne]
    private ?Answer $selectedAnswer = null;

    public function __construct()
    {
        $this->answeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getTimeToAnswer(): ?int
    {
        return $this->timeToAnswer;
    }

    public function setTimeToAnswer(int $timeToAnswer): static
    {
        $this->timeToAnswer = $timeToAnswer;

        return $this;
    }

    public function getAnsweredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(\DateTimeImmutable $answeredAt): static
    {
        $this->answeredAt = $answeredAt;

        return $this;
    }

    public function getGameParticipant(): ?GameParticipant
    {
        return $this->gameParticipant;
    }

    public function setGameParticipant(?GameParticipant $gameParticipant): static
    {
        $this->gameParticipant = $gameParticipant;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getSelectedAnswer(): ?Answer
    {
        return $this->selectedAnswer;
    }

    public function setSelectedAnswer(?Answer $selectedAnswer): static
    {
        $this->selectedAnswer = $selectedAnswer;

        return $this;
    }
}
