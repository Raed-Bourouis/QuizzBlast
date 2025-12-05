<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_SINGLE_CHOICE = 'single_choice';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $questionType = self::TYPE_SINGLE_CHOICE;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    #[ORM\Column]
    private ?int $points = null;

    #[ORM\Column]
    private ?int $timeLimit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mediaUrl = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    private ?Quiz $quiz = null;

    /**
     * @var Collection<int, Answer>
     */
    #[ORM\OneToMany(targetEntity: Answer::class, mappedBy: 'question', cascade: ['persist', 'remove'])]
    private Collection $answers;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionType(): ?string
    {
        return $this->questionType;
    }

    public function setQuestionType(string $questionType): static
    {
        $this->questionType = $questionType;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
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

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(int $timeLimit): static
    {
        $this->timeLimit = $timeLimit;

        return $this;
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(?string $mediaUrl): static
    {
        $this->mediaUrl = $mediaUrl;

        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): static
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setQuestion($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): static
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }

        return $this;
    }

    #[Assert\Callback]
    public function validateQuestionType(ExecutionContextInterface $context): void
    {
        // General: Ensure the question has at least one answer
        if ($this->answers->isEmpty()) {
            $context->buildViolation('A question must have at least one answer.')
                ->atPath('answers')
                ->addViolation();
            return;
        }

        // Count correct answers
        $correctAnswersCount = 0;
        foreach ($this->answers as $answer) {
            if ($answer->isCorrect() === true) {
                $correctAnswersCount++;
            }
        }

        switch ($this->questionType) {
            case self::TYPE_SINGLE_CHOICE:
                if ($correctAnswersCount !== 1) {
                    $context->buildViolation('A single choice question must have exactly one correct answer.')
                        ->atPath('answers')
                        ->addViolation();
                }
                break;

            case self::TYPE_MULTIPLE_CHOICE:
                if ($correctAnswersCount < 1) {
                    $context->buildViolation('A multiple choice question must have at least one correct answer.')
                        ->atPath('answers')
                        ->addViolation();
                }
                break;

            case self::TYPE_TRUE_FALSE:
                if ($this->answers->count() !== 2) {
                    $context->buildViolation('A true/false question must have exactly 2 answers.')
                        ->atPath('answers')
                        ->addViolation();
                }
                if ($correctAnswersCount !== 1) {
                    $context->buildViolation('A true/false question must have exactly one correct answer.')
                        ->atPath('answers')
                        ->addViolation();
                }
                break;
        }
    }
}
