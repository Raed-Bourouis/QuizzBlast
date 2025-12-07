<?php

namespace App\Tests\Validator;

use App\Entity\Answer;
use App\Entity\Question;
use App\Validator\ValidQuestionAnswers;
use App\Validator\ValidQuestionAnswersValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidQuestionAnswersValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidQuestionAnswersValidator
    {
        return new ValidQuestionAnswersValidator();
    }

    public function testValidSingleChoiceQuestion(): void
    {
        $question = new Question();
        $question->setQuestionType(Question::TYPE_SINGLE_CHOICE);
        $question->setText('What is the capital of France?');
        $question->setPoints(10);
        $question->setTimeLimit(30);

        // Add two answers, one correct
        $answer1 = new Answer();
        $answer1->setText('Paris');
        $answer1->setIsCorrect(true);
        $answer1->setOrderIndex(0);
        $question->addAnswer($answer1);

        $answer2 = new Answer();
        $answer2->setText('London');
        $answer2->setIsCorrect(false);
        $answer2->setOrderIndex(1);
        $question->addAnswer($answer2);

        $constraint = new ValidQuestionAnswers();
        $this->validator->validate($question, $constraint);

        $this->assertNoViolation();
    }

    public function testInvalidSingleChoiceQuestionMultipleCorrect(): void
    {
        $question = new Question();
        $question->setQuestionType(Question::TYPE_SINGLE_CHOICE);
        $question->setText('What is the capital of France?');
        $question->setPoints(10);
        $question->setTimeLimit(30);

        // Add two answers, both correct (invalid for single choice)
        $answer1 = new Answer();
        $answer1->setText('Paris');
        $answer1->setIsCorrect(true);
        $answer1->setOrderIndex(0);
        $question->addAnswer($answer1);

        $answer2 = new Answer();
        $answer2->setText('Also Paris');
        $answer2->setIsCorrect(true);
        $answer2->setOrderIndex(1);
        $question->addAnswer($answer2);

        $constraint = new ValidQuestionAnswers();
        $this->validator->validate($question, $constraint);

        $this->buildViolation($constraint->messageSingleChoiceOneCorrect)
            ->assertRaised();
    }

    public function testValidTrueFalseQuestion(): void
    {
        $question = new Question();
        $question->setQuestionType(Question::TYPE_TRUE_FALSE);
        $question->setText('The sky is blue');
        $question->setPoints(5);
        $question->setTimeLimit(15);

        $answer1 = new Answer();
        $answer1->setText('True');
        $answer1->setIsCorrect(true);
        $answer1->setOrderIndex(0);
        $question->addAnswer($answer1);

        $answer2 = new Answer();
        $answer2->setText('False');
        $answer2->setIsCorrect(false);
        $answer2->setOrderIndex(1);
        $question->addAnswer($answer2);

        $constraint = new ValidQuestionAnswers();
        $this->validator->validate($question, $constraint);

        $this->assertNoViolation();
    }

    public function testInvalidTrueFalseQuestionTooManyAnswers(): void
    {
        $question = new Question();
        $question->setQuestionType(Question::TYPE_TRUE_FALSE);
        $question->setText('The sky is blue');
        $question->setPoints(5);
        $question->setTimeLimit(15);

        $answer1 = new Answer();
        $answer1->setText('True');
        $answer1->setIsCorrect(true);
        $answer1->setOrderIndex(0);
        $question->addAnswer($answer1);

        $answer2 = new Answer();
        $answer2->setText('False');
        $answer2->setIsCorrect(false);
        $answer2->setOrderIndex(1);
        $question->addAnswer($answer2);

        $answer3 = new Answer();
        $answer3->setText('Maybe');
        $answer3->setIsCorrect(false);
        $answer3->setOrderIndex(2);
        $question->addAnswer($answer3);

        $constraint = new ValidQuestionAnswers();
        $this->validator->validate($question, $constraint);

        $this->buildViolation($constraint->messageTrueFalseExact)
            ->assertRaised();
    }

    public function testInvalidQuestionTooFewAnswers(): void
    {
        $question = new Question();
        $question->setQuestionType(Question::TYPE_SINGLE_CHOICE);
        $question->setText('What is 2+2?');
        $question->setPoints(5);
        $question->setTimeLimit(10);

        // Only one answer (should require at least 2)
        $answer1 = new Answer();
        $answer1->setText('4');
        $answer1->setIsCorrect(true);
        $answer1->setOrderIndex(0);
        $question->addAnswer($answer1);

        $constraint = new ValidQuestionAnswers();
        $this->validator->validate($question, $constraint);

        $this->buildViolation($constraint->messageTooFewAnswers)
            ->setParameter('{{ type }}', Question::TYPE_SINGLE_CHOICE)
            ->setParameter('{{ min }}', '2')
            ->assertRaised();
    }

    public function testInvalidQuestionNoCorrectAnswer(): void
    {
        $question = new Question();
        $question->setQuestionType(Question::TYPE_MULTIPLE_CHOICE);
        $question->setText('Select all prime numbers');
        $question->setPoints(10);
        $question->setTimeLimit(30);

        // All answers are incorrect
        $answer1 = new Answer();
        $answer1->setText('4');
        $answer1->setIsCorrect(false);
        $answer1->setOrderIndex(0);
        $question->addAnswer($answer1);

        $answer2 = new Answer();
        $answer2->setText('6');
        $answer2->setIsCorrect(false);
        $answer2->setOrderIndex(1);
        $question->addAnswer($answer2);

        $constraint = new ValidQuestionAnswers();
        $this->validator->validate($question, $constraint);

        $this->buildViolation($constraint->messageNoCorrectAnswer)
            ->assertRaised();
    }

    public function testValidMultipleChoiceQuestion(): void
    {
        $question = new Question();
        $question->setQuestionType(Question::TYPE_MULTIPLE_CHOICE);
        $question->setText('Select all prime numbers');
        $question->setPoints(15);
        $question->setTimeLimit(45);

        $answer1 = new Answer();
        $answer1->setText('2');
        $answer1->setIsCorrect(true);
        $answer1->setOrderIndex(0);
        $question->addAnswer($answer1);

        $answer2 = new Answer();
        $answer2->setText('3');
        $answer2->setIsCorrect(true);
        $answer2->setOrderIndex(1);
        $question->addAnswer($answer2);

        $answer3 = new Answer();
        $answer3->setText('4');
        $answer3->setIsCorrect(false);
        $answer3->setOrderIndex(2);
        $question->addAnswer($answer3);

        $constraint = new ValidQuestionAnswers();
        $this->validator->validate($question, $constraint);

        $this->assertNoViolation();
    }
}
