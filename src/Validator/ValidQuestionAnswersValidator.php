<?php

namespace App\Validator;

use App\Entity\Question;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidQuestionAnswersValidator extends ConstraintValidator
{
    private const MINIMUM_ANSWER_COUNT = 2;
    private const TRUE_FALSE_ANSWER_COUNT = 2;
    private const SINGLE_CHOICE_CORRECT_ANSWER_COUNT = 1;

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidQuestionAnswers) {
            throw new UnexpectedTypeException($constraint, ValidQuestionAnswers::class);
        }

        if (!$value instanceof Question) {
            throw new UnexpectedTypeException($value, Question::class);
        }

        $answers = $value->getAnswers();
        $answerCount = $answers->count();
        $questionType = $value->getQuestionType();

        // Check minimum answers based on question type
        if ($answerCount < self::MINIMUM_ANSWER_COUNT) {
            $this->context->buildViolation($constraint->messageTooFewAnswers)
                ->setParameter('{{ type }}', $questionType)
                ->setParameter('{{ min }}', (string) self::MINIMUM_ANSWER_COUNT)
                ->addViolation();
            return;
        }

        // Check for at least one correct answer
        $correctAnswerCount = 0;
        foreach ($answers as $answer) {
            if ($answer->isCorrect()) {
                $correctAnswerCount++;
            }
        }

        if ($correctAnswerCount === 0) {
            $this->context->buildViolation($constraint->messageNoCorrectAnswer)
                ->addViolation();
            return;
        }

        // Specific validation for TRUE_FALSE questions
        if ($questionType === Question::TYPE_TRUE_FALSE) {
            if ($answerCount !== self::TRUE_FALSE_ANSWER_COUNT) {
                $this->context->buildViolation($constraint->messageTrueFalseExact)
                    ->addViolation();
                return;
            }
        }

        // Specific validation for SINGLE_CHOICE questions
        if ($questionType === Question::TYPE_SINGLE_CHOICE) {
            if ($correctAnswerCount !== self::SINGLE_CHOICE_CORRECT_ANSWER_COUNT) {
                $this->context->buildViolation($constraint->messageSingleChoiceOneCorrect)
                    ->addViolation();
                return;
            }
        }
    }
}
