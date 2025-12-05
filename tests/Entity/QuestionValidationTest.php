<?php

namespace App\Tests\Entity;

use App\Entity\Answer;
use App\Entity\Question;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuestionValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    private function createAnswer(bool $isCorrect): Answer
    {
        $answer = new Answer();
        $answer->setText('Test Answer');
        $answer->setIsCorrect($isCorrect);
        $answer->setOrderIndex(0);
        return $answer;
    }

    private function createQuestion(string $type): Question
    {
        $question = new Question();
        $question->setQuestionType($type);
        $question->setText('Test Question');
        $question->setPoints(10);
        $question->setTimeLimit(30);
        return $question;
    }

    /**
     * Filter violations to only include callback-related violations.
     *
     * @return array<\Symfony\Component\Validator\ConstraintViolationInterface>
     */
    private function filterCallbackViolations(ConstraintViolationListInterface $violations): array
    {
        $keywords = ['single choice', 'multiple choice', 'true/false', 'must have at least one answer'];
        return array_filter(
            iterator_to_array($violations),
            function ($v) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($v->getMessage(), $keyword)) {
                        return true;
                    }
                }
                return false;
            }
        );
    }

    /**
     * Check if a specific message is found in the violations.
     */
    private function hasViolationContaining(ConstraintViolationListInterface $violations, string $needle): bool
    {
        foreach ($violations as $violation) {
            if (str_contains($violation->getMessage(), $needle)) {
                return true;
            }
        }
        return false;
    }

    // General Validation Tests
    public function testQuestionMustHaveAtLeastOneAnswer(): void
    {
        $question = $this->createQuestion(Question::TYPE_SINGLE_CHOICE);
        // No answers added

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString(
            'A question must have at least one answer',
            $violations[0]->getMessage()
        );
    }

    // Single Choice Validation Tests
    public function testSingleChoiceWithExactlyOneCorrectAnswerIsValid(): void
    {
        $question = $this->createQuestion(Question::TYPE_SINGLE_CHOICE);
        $question->addAnswer($this->createAnswer(true));
        $question->addAnswer($this->createAnswer(false));

        $violations = $this->validator->validate($question);
        $callbackViolations = $this->filterCallbackViolations($violations);

        $this->assertCount(0, $callbackViolations);
    }

    public function testSingleChoiceWithNoCorrectAnswerIsInvalid(): void
    {
        $question = $this->createQuestion(Question::TYPE_SINGLE_CHOICE);
        $question->addAnswer($this->createAnswer(false));
        $question->addAnswer($this->createAnswer(false));

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertTrue(
            $this->hasViolationContaining($violations, 'exactly one correct answer'),
            'Expected violation for single choice with no correct answer'
        );
    }

    public function testSingleChoiceWithMultipleCorrectAnswersIsInvalid(): void
    {
        $question = $this->createQuestion(Question::TYPE_SINGLE_CHOICE);
        $question->addAnswer($this->createAnswer(true));
        $question->addAnswer($this->createAnswer(true));

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertTrue(
            $this->hasViolationContaining($violations, 'exactly one correct answer'),
            'Expected violation for single choice with multiple correct answers'
        );
    }

    // Multiple Choice Validation Tests
    public function testMultipleChoiceWithAtLeastOneCorrectAnswerIsValid(): void
    {
        $question = $this->createQuestion(Question::TYPE_MULTIPLE_CHOICE);
        $question->addAnswer($this->createAnswer(true));
        $question->addAnswer($this->createAnswer(true));
        $question->addAnswer($this->createAnswer(false));

        $violations = $this->validator->validate($question);
        $callbackViolations = $this->filterCallbackViolations($violations);

        $this->assertCount(0, $callbackViolations);
    }

    public function testMultipleChoiceWithNoCorrectAnswerIsInvalid(): void
    {
        $question = $this->createQuestion(Question::TYPE_MULTIPLE_CHOICE);
        $question->addAnswer($this->createAnswer(false));
        $question->addAnswer($this->createAnswer(false));

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertTrue(
            $this->hasViolationContaining($violations, 'at least one correct answer'),
            'Expected violation for multiple choice with no correct answer'
        );
    }

    // True/False Validation Tests
    public function testTrueFalseWithExactlyTwoAnswersAndOneCorrectIsValid(): void
    {
        $question = $this->createQuestion(Question::TYPE_TRUE_FALSE);
        $question->addAnswer($this->createAnswer(true));
        $question->addAnswer($this->createAnswer(false));

        $violations = $this->validator->validate($question);
        $callbackViolations = $this->filterCallbackViolations($violations);

        $this->assertCount(0, $callbackViolations);
    }

    public function testTrueFalseWithLessThanTwoAnswersIsInvalid(): void
    {
        $question = $this->createQuestion(Question::TYPE_TRUE_FALSE);
        $question->addAnswer($this->createAnswer(true));

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertTrue(
            $this->hasViolationContaining($violations, 'exactly 2 answers'),
            'Expected violation for true/false with less than 2 answers'
        );
    }

    public function testTrueFalseWithMoreThanTwoAnswersIsInvalid(): void
    {
        $question = $this->createQuestion(Question::TYPE_TRUE_FALSE);
        $question->addAnswer($this->createAnswer(true));
        $question->addAnswer($this->createAnswer(false));
        $question->addAnswer($this->createAnswer(false));

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertTrue(
            $this->hasViolationContaining($violations, 'exactly 2 answers'),
            'Expected violation for true/false with more than 2 answers'
        );
    }

    public function testTrueFalseWithNoCorrectAnswerIsInvalid(): void
    {
        $question = $this->createQuestion(Question::TYPE_TRUE_FALSE);
        $question->addAnswer($this->createAnswer(false));
        $question->addAnswer($this->createAnswer(false));

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertTrue(
            $this->hasViolationContaining($violations, 'exactly one correct answer'),
            'Expected violation for true/false with no correct answer'
        );
    }

    public function testTrueFalseWithTwoCorrectAnswersIsInvalid(): void
    {
        $question = $this->createQuestion(Question::TYPE_TRUE_FALSE);
        $question->addAnswer($this->createAnswer(true));
        $question->addAnswer($this->createAnswer(true));

        $violations = $this->validator->validate($question);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertTrue(
            $this->hasViolationContaining($violations, 'exactly one correct answer'),
            'Expected violation for true/false with two correct answers'
        );
    }
}
