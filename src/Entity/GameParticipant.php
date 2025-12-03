<?php

namespace App\Entity;

use App\Repository\GameParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameParticipantRepository::class)]
class GameParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nickname = null;

    #[ORM\Column]
    private ?int $totalScore = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    private ?GameSession $gameSession = null;

    #[ORM\ManyToOne]
    private ?User $user = null;

    /**
     * @var Collection<int, PlayerAnswer>
     */
    #[ORM\OneToMany(targetEntity: PlayerAnswer::class, mappedBy: 'gameParticipant')]
    private Collection $playerAnswers;

    public function __construct()
    {
        $this->playerAnswers = new ArrayCollection();
        $this->joinedAt = new \DateTimeImmutable();
        $this->totalScore = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(int $totalScore): static
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getGameSession(): ?GameSession
    {
        return $this->gameSession;
    }

    public function setGameSession(?GameSession $gameSession): static
    {
        $this->gameSession = $gameSession;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, PlayerAnswer>
     */
    public function getPlayerAnswers(): Collection
    {
        return $this->playerAnswers;
    }

    public function addPlayerAnswer(PlayerAnswer $playerAnswer): static
    {
        if (!$this->playerAnswers->contains($playerAnswer)) {
            $this->playerAnswers->add($playerAnswer);
            $playerAnswer->setGameParticipant($this);
        }

        return $this;
    }

    public function removePlayerAnswer(PlayerAnswer $playerAnswer): static
    {
        if ($this->playerAnswers->removeElement($playerAnswer)) {
            // set the owning side to null (unless already changed)
            if ($playerAnswer->getGameParticipant() === $this) {
                $playerAnswer->setGameParticipant(null);
            }
        }

        return $this;
    }
}
