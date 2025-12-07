<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextareaType::class, [
                'label' => 'Question Text',
            ])
            ->add('questionType', ChoiceType::class, [
                'label' => 'Question Type',
                'choices' => [
                    'Single Choice' => Question::TYPE_SINGLE_CHOICE,
                    'Multiple Choice' => Question::TYPE_MULTIPLE_CHOICE,
                    'True/False' => Question::TYPE_TRUE_FALSE,
                ],
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Points',
            ])
            ->add('timeLimit', IntegerType::class, [
                'label' => 'Time Limit (seconds)',
            ])
            ->add('mediaUrl', TextType::class, [
                'label' => 'Media URL',
                'required' => false,
            ])
            ->add('answers', CollectionType::class, [
                'entry_type' => AnswerType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Answers',
                'attr' => ['class' => 'answers-collection'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}
