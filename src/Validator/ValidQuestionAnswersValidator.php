<?php

namespace App\Validator;

use App\Entity\Question;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidQuestionAnswersValidator extends ConstraintValidator
{
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
        if ($answerCount < 2) {
            $this->context->buildViolation($constraint->messageTooFewAnswers)
                ->setParameter('{{ type }}', $questionType)
                ->setParameter('{{ min }}', '2')
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
            if ($answerCount !== 2) {
                $this->context->buildViolation($constraint->messageTrueFalseExact)
                    ->addViolation();
                return;
            }
        }

        // Specific validation for SINGLE_CHOICE questions
        if ($questionType === Question::TYPE_SINGLE_CHOICE) {
            if ($correctAnswerCount !== 1) {
                $this->context->buildViolation($constraint->messageSingleChoiceOneCorrect)
                    ->addViolation();
                return;
            }
        }
    }
}
