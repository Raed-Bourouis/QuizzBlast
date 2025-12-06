<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidQuestionAnswers extends Constraint
{
    public string $messageTooFewAnswers = 'A question of type "{{ type }}" must have at least {{ min }} answer(s).';
    public string $messageNoCorrectAnswer = 'A question must have at least one correct answer.';
    public string $messageTrueFalseExact = 'A True/False question must have exactly 2 answers.';
    public string $messageSingleChoiceOneCorrect = 'A Single Choice question must have exactly one correct answer.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
