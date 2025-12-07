<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/quiz')]
final class QuizController extends AbstractController
{
    #[Route('/', name: 'app_quiz_index', methods: ['GET'])]
    public function index(QuizRepository $quizRepository): Response
    {
        $quizzes = $quizRepository->findBy([
            'isPublic' => true,
            'isActive' => true,
        ]);

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizzes,
        ]);
    }

    #[Route('/my-quizzes', name: 'app_quiz_my_quizzes', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myQuizzes(QuizRepository $quizRepository): Response
    {
        $quizzes = $quizRepository->findBy([
            'createdBy' => $this->getUser(),
        ]);

        return $this->render('quiz/my_quizzes.html.twig', [
            'quizzes' => $quizzes,
        ]);
    }

    #[Route('/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creator
            $quiz->setCreatedBy($this->getUser());
            
            // CRITICAL FIX: Synchronize relationships before persistence
            $this->synchronizeQuizRelationships($quiz);
            
            // Persist the quiz (cascade operations will persist questions and answers)
            $entityManager->persist($quiz);
            $entityManager->flush();

            $this->addFlash('success', 'Quiz created successfully!');
            return $this->redirectToRoute('app_quiz_show', ['id' => $quiz->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quiz/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): Response
    {
        return $this->render('quiz/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        // Check if current user is the owner
        if ($quiz->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to edit this quiz.');
        }

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // CRITICAL FIX: Synchronize relationships before flush
            $this->synchronizeQuizRelationships($quiz);
            
            $entityManager->flush();

            $this->addFlash('success', 'Quiz updated successfully!');
            return $this->redirectToRoute('app_quiz_show', ['id' => $quiz->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quiz/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        // Check if current user is the owner
        if ($quiz->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not allowed to delete this quiz.');
        }

        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();
            
            $this->addFlash('success', 'Quiz deleted successfully!');
        }

        return $this->redirectToRoute('app_quiz_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Synchronizes bidirectional relationships for Quiz → Question → Answer.
     * This ensures Doctrine can properly track and persist the entire object graph.
     */
    private function synchronizeQuizRelationships(Quiz $quiz): void
    {
        foreach ($quiz->getQuestions() as $question) {
            $question->setQuiz($quiz);
            
            foreach ($question->getAnswers() as $answer) {
                $answer->setQuestion($question);
            }
        }
    }
}
