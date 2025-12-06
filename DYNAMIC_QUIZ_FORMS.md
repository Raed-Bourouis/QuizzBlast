# Dynamic Quiz Forms Documentation

This document describes the implementation of dynamic form functionality for creating and editing Quizzes with Questions and Answers using Symfony CollectionType.

## Overview

The Quiz application allows users to dynamically add and remove Questions and their associated Answers when creating or editing a Quiz. This is accomplished using Symfony's `CollectionType` along with JavaScript for client-side interactivity.

## Features

### 1. Dynamic Question Management
- **Add Questions**: Users can add new questions dynamically without page refresh
- **Remove Questions**: Users can remove questions with a single click
- Each question includes fields for:
  - Question text
  - Question type (Single Choice, Multiple Choice, True/False)
  - Points awarded
  - Time limit in seconds
  - Optional media URL

### 2. Dynamic Answer Management
- **Add Answers**: Within each question, users can add multiple answer options
- **Remove Answers**: Users can remove individual answers
- Each answer includes fields for:
  - Answer text
  - Is Correct checkbox
  - Order index

### 3. Nested Collections
- Questions are a collection within the Quiz form
- Answers are a nested collection within each Question form
- Both levels support dynamic add/remove operations

## Implementation Details

### Entity Relationships

#### Quiz → Questions
```php
#[ORM\OneToMany(
    targetEntity: Question::class, 
    mappedBy: 'quiz', 
    cascade: ['persist', 'remove']
)]
private Collection $questions;
```

#### Question → Answers
```php
#[ORM\OneToMany(
    targetEntity: Answer::class, 
    mappedBy: 'question', 
    cascade: ['persist', 'remove']
)]
private Collection $answers;
```

**Key Features:**
- Bidirectional relationships allow navigation in both directions
- `cascade: ['persist', 'remove']` ensures that when a Quiz is saved, all related Questions and Answers are automatically persisted
- When a Quiz is deleted, all associated Questions and Answers are also removed

### Form Types

#### QuizType
```php
->add('questions', CollectionType::class, [
    'entry_type' => QuestionType::class,
    'entry_options' => ['label' => false],
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
    'label' => 'Questions',
])
```

#### QuestionType
```php
->add('answers', CollectionType::class, [
    'entry_type' => AnswerType::class,
    'entry_options' => ['label' => false],
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
    'label' => 'Answers',
])
```

**Configuration:**
- `entry_type`: Specifies the form type for each collection item
- `allow_add`: Enables dynamic addition of new items
- `allow_delete`: Enables dynamic removal of items
- `by_reference => false`: Critical for proper cascade handling - ensures add/remove methods are called on the entity

### Templates

Both `templates/quiz/new.html.twig` and `templates/quiz/edit.html.twig` include:

#### Data Prototype Attributes
```twig
<div class="questions-collection" 
     data-prototype="{{ form_widget(form.questions.vars.prototype)|e('html_attr') }}" 
     data-index="{{ form.questions|length }}">
```

The `data-prototype` attribute contains the HTML template for a new form field, with placeholder `__name__` that gets replaced with the actual index.

#### Styling
- Collection items are visually distinguished with borders and background colors
- Nested collections (answers) have different styling than parent collections (questions)
- Buttons are color-coded: green for add, red for remove

### JavaScript Implementation

The JavaScript code in both templates handles:

#### Adding Questions
1. Retrieves the prototype from `data-prototype` attribute
2. Replaces `__name__` placeholder with current index
3. Creates DOM elements and appends to collection
4. Adds "Add Answer" and "Remove Question" buttons
5. Increments the index counter

#### Adding Answers
1. Similar process as adding questions
2. Finds the answers collection within the question
3. Manages separate index for each question's answers

#### Removing Items
1. Event listeners on remove buttons
2. Uses `closest()` to find parent element
3. Removes the entire collection item from DOM

#### Key JavaScript Features
- **Null Checks**: All `getAttribute()` calls include null checks to prevent TypeErrors
- **Index Management**: Separate index tracking for questions and answers ensures unique field names
- **Event Delegation**: Event listeners set up for both existing and dynamically added elements

## Usage

### Creating a New Quiz

1. Navigate to `/quiz/new`
2. Fill in quiz details (title, description, difficulty, etc.)
3. Click "Add Question" to add questions
4. For each question, click "Add Answer" to add answer options
5. Mark the correct answer(s) using checkboxes
6. Click "Remove Question" or "Remove Answer" to delete unwanted items
7. Submit the form to save

### Editing an Existing Quiz

1. Navigate to `/quiz/{id}/edit`
2. Existing questions and answers are displayed
3. Use the same add/remove functionality as creation
4. Changes are persisted on form submission

## Data Persistence

### On Save
- Symfony processes the entire nested form data
- `by_reference => false` ensures collection add/remove methods are called
- Cascade operations automatically persist related entities
- Doctrine manages the relationships and foreign keys

### On Delete
- When removing a question/answer in the UI, the item is simply not submitted
- Symfony's CollectionType with `allow_delete => true` handles removal
- Database cascade rules ensure orphaned records are cleaned up

## Best Practices

1. **Always use `by_reference => false`** in CollectionType configuration for proper cascade handling
2. **Include null checks** in JavaScript when accessing DOM attributes
3. **Use `cascade: ['persist', 'remove']`** in entity relationships for automatic persistence
4. **HTML escape the prototype** using `e('html_attr')` to prevent XSS
5. **Track indices separately** for nested collections to avoid naming conflicts

## Troubleshooting

### Items not persisting
- Verify `by_reference => false` is set in the form type
- Check that entity add/remove methods update both sides of the relationship

### JavaScript errors
- Ensure `data-prototype` and `data-index` attributes are present
- Verify querySelector/closest selectors match actual HTML structure
- Check browser console for specific error messages

### Cascade operations not working
- Confirm `cascade` is configured in ORM mapping
- Verify the owning side of the relationship sets the inverse side

## Migration Notes

The application includes a migration (`Version20251206193746`) that adds:
- `question_type` column to the `question` table (default: 'single_choice')
- `category` column to the `quiz` table (default: 'General')
- `is_verified` column to the `user` table (default: 0)

These columns have default values to support existing data.

## Security Considerations

- Form submissions are protected by Symfony's CSRF tokens
- User authentication required for quiz creation/editing (`#[IsGranted('ROLE_USER')]`)
- Only quiz creators can edit their own quizzes
- Input is validated through Symfony's form validation system
- HTML in prototypes is properly escaped to prevent XSS

## Browser Compatibility

The JavaScript implementation uses modern ES6+ features:
- Arrow functions
- Template literals
- `querySelector` / `querySelectorAll`
- `closest()`
- `forEach` on NodeLists

Supported browsers: All modern browsers (Chrome, Firefox, Safari, Edge)
