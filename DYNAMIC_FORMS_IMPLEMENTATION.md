# Dynamic Nested Forms Implementation

This document describes the implementation of dynamic nested forms for managing answers in Question forms.

## Overview

The application now supports dynamically adding and removing answer fields when creating or editing questions in quizzes. This feature provides better interactivity and flexibility for quiz creators.

## Key Features

### 1. Form Types with CollectionType

#### QuestionType (`src/Form/QuestionType.php`)
- Uses `CollectionType` for the `answers` field
- Configuration:
  - `entry_type`: `AnswerType::class`
  - `allow_add`: `true` - Allows adding new answers dynamically
  - `allow_delete`: `true` - Allows removing answers dynamically
  - `by_reference`: `false` - Ensures proper cascade operations with Doctrine

#### AnswerType (`src/Form/AnswerType.php`)
- Individual answer form with fields:
  - `text`: Answer text
  - `isCorrect`: Checkbox for marking correct answers
  - `orderIndex`: Order of the answer

### 2. Entity Validation

#### Question Entity (`src/Entity/Question.php`)
Validation constraints:
- `questionType`: Must be one of `single_choice`, `multiple_choice`, or `true_false`
- `text`: Required, min 5 chars, max 1000 chars
- `points`: Required, must be positive
- `timeLimit`: Required, must be positive
- `answers`: At least 1 answer required, child entities validated

Custom validation via `ValidQuestionAnswers` constraint.

#### Answer Entity (`src/Entity/Answer.php`)
Validation constraints:
- `text`: Required, min 1 char, max 255 chars
- `isCorrect`: Required (must be explicitly true or false)
- `orderIndex`: Required, must be zero or positive

### 3. Custom Validator

#### ValidQuestionAnswers (`src/Validator/ValidQuestionAnswers.php`)
Custom constraint that validates:

1. **Minimum Answers**: All questions must have at least 2 answers
2. **At Least One Correct Answer**: Every question must have at least one correct answer
3. **TRUE_FALSE Specific**: Must have exactly 2 answers
4. **SINGLE_CHOICE Specific**: Must have exactly 1 correct answer

#### ValidQuestionAnswersValidator (`src/Validator/ValidQuestionAnswersValidator.php`)
Implements the validation logic for the custom constraint.

### 4. JavaScript Implementation

#### Dynamic Form Script (`templates/question/_form_script.html.twig`)
Reusable JavaScript that handles:

**Question Management:**
- `addQuestion()`: Adds a new question to the collection
- `removeQuestion()`: Removes a question with confirmation
- `updateQuestionNumbers()`: Updates question numbering after add/remove

**Answer Management:**
- `addAnswer()`: Adds a new answer field to a question
- `removeAnswer()`: Removes an answer (enforces minimum of 2 answers)

**Key Features:**
- Null checks before using `getAttribute()` to prevent TypeErrors
- Uses the `data-prototype` attribute from Symfony's CollectionType
- Properly replaces `__name__` placeholders with actual indices
- Dynamically attaches event listeners to new elements

**Usage in Templates:**
```twig
{% include 'question/_form_script.html.twig' %}
```

### 5. Templates

#### Quiz Forms
Both `templates/quiz/new.html.twig` and `templates/quiz/edit.html.twig` use the dynamic forms:

```twig
<div class="questions-collection" 
     data-prototype="{{ form_widget(form.questions.vars.prototype)|e('html_attr') }}" 
     data-index="{{ form.questions|length }}">
    {% for question in form.questions %}
        {# Question form fields #}
        <div class="answers-collection" 
             data-prototype="{{ form_widget(question.answers.vars.prototype)|e('html_attr') }}" 
             data-index="{{ question.answers|length }}">
            {% for answer in question.answers %}
                {# Answer form fields #}
            {% endfor %}
        </div>
    {% endfor %}
</div>
```

## Testing

### ValidQuestionAnswersValidatorTest
Location: `tests/Validator/ValidQuestionAnswersValidatorTest.php`

Tests cover:
- ✓ Valid single choice question (1 correct answer)
- ✓ Invalid single choice with multiple correct answers
- ✓ Valid true/false question (exactly 2 answers)
- ✓ Invalid true/false with too many answers
- ✓ Invalid question with too few answers (< 2)
- ✓ Invalid question with no correct answer
- ✓ Valid multiple choice question (multiple correct answers)

All tests pass successfully.

## Usage

### Creating a Quiz

1. Navigate to `/quiz/new`
2. Fill in quiz information
3. Click "Add New Question" to add questions
4. For each question:
   - Enter question text, type, points, and time limit
   - Click "Add Answer" to add answer options
   - Mark correct answers with the checkbox
   - Click "Remove Answer" to remove unwanted answers (minimum 2 required)
5. Click "Remove Question" to delete entire questions
6. Submit the form to create the quiz

### Validation Behavior

The form will validate on submission and show errors if:
- A question has less than 2 answers
- A question has no correct answer
- A TRUE/FALSE question doesn't have exactly 2 answers
- A SINGLE_CHOICE question has more than 1 correct answer
- Required fields are missing or invalid

## Browser Compatibility

The JavaScript uses standard DOM APIs and should work in all modern browsers:
- Chrome/Edge (Chromium)
- Firefox
- Safari
- Opera

## Future Enhancements

Possible improvements:
- Drag-and-drop reordering of answers
- Bulk import of questions from CSV/JSON
- Question templates for common patterns
- Rich text editor for question text
- Image upload for answer options
