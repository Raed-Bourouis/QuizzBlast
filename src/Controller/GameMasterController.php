<?php

namespace App\Controller;

use App\Entity\GameSession;
use App\Repository\GameSessionRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameMasterController extends AbstractController
{
    /**
     * Démarre une nouvelle partie.
     */
    #[Route('/game/start/{id}', name: 'game_start', methods: ['GET'])]
    public function start(
        int $id,
        QuizRepository $quizRepo,
        GameSessionRepository $sessionRepo,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $quiz = $quizRepo->find($id);
        if (!$quiz) {
            throw $this->createNotFoundException('Quiz introuvable.');
        }

        $code = $this->generateUniqueCode($sessionRepo);

        $session = new GameSession();
        $session->setQuiz($quiz);
        $session->setHost($this->getUser());
        $session->setCode($code);
        $session->setStatus(GameSession::STATUS_WAITING);
        $session->setCurrentQuestionIndex(0);

        $em->persist($session);
        $em->flush();

        return $this->render('game_master/host.html.twig', [
            'session' => $session,
        ]);
    }

    /**
     * ⚡ Commencer la partie
     */
    #[Route('/game/{code}/start-game', name: 'game_start_game', methods: ['POST'])]
    public function startGame(
        string $code,
        GameSessionRepository $sessionRepo,
        EntityManagerInterface $em
    ): JsonResponse 
    {
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable'], 404);
        }

        $session->setStatus(GameSession::STATUS_IN_PROGRESS);
        $session->setCurrentQuestionIndex(0);

        $em->flush();

        return new JsonResponse(['message' => 'Game started']);
    }

    /**
     * ➡ Aller à la question suivante
     */
    #[Route('/game/{code}/next', name: 'game_next_question', methods: ['POST'])]
    public function nextQuestion(
        string $code,
        GameSessionRepository $sessionRepo,
        EntityManagerInterface $em
    ): JsonResponse 
    {
        $session = $sessionRepo->findOneBy(['code' => $code]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable'], 404);
        }

        $quiz = $session->getQuiz();
        $questions = $quiz->getQuestions();

        $currentIndex = $session->getCurrentQuestionIndex();
        $total = count($questions);

        // Fin du quiz ?
        if ($currentIndex >= $total - 1) {
            $session->setStatus(GameSession::STATUS_FINISHED);
            $em->flush();
            return new JsonResponse(['finished' => true]);
        }

        // Sinon passer à la question suivante
        $session->setCurrentQuestionIndex($currentIndex + 1);
        $em->flush();

        return new JsonResponse([
            'finished' => false,
            'index' => $session->getCurrentQuestionIndex()
        ]);
    }


    /**
     * 🔄 Endpoint de polling pour front (état de la session)
     * Retourne :
     * - status
     * - currentQuestion
     * - participants
     */
    #[Route('/game/{code}/state', name: 'game_state', methods: ['GET'])]
    public function getState(
        string $code,
        GameSessionRepository $sessionRepo,
        GameParticipantRepository $participantRepo
    ): JsonResponse
    {
        $session = $sessionRepo->findOneBy(['code' => $code]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable'], 404);
        }

        $participants = $participantRepo->findBy(['gameSession' => $session]);

        return new JsonResponse([
            'status' => $session->getStatus(),
            'currentQuestionIndex' => $session->getCurrentQuestionIndex(),
            'participants' => array_map(function($p) {
                return [
                    'id' => $p->getId(),
                    'username' => $p->getUsername(),
                    'score' => $p->getTotalScore(),
                ];
            }, $participants)
        ]);
    }


    /**
     * Termine la partie (déjà fait)
     */
    #[Route('/game/{code}/end', name: 'game_end', methods: ['GET'])]
    public function end(
        string $code,
        GameSessionRepository $sessionRepo,
        GameParticipantRepository $participantRepo,
        EntityManagerInterface $em
    ): Response {
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            throw $this->createNotFoundException('Session introuvable.');
        }

        $participants = $participantRepo->findBy(['gameSession' => $session]);

        foreach ($participants as $participant) {
            $total = 0;
            foreach ($participant->getPlayerAnswers() as $ans) {
                $total += $ans->getPoints() ?? 0;
            }
            $participant->setTotalScore($total);
        }

        $session->setStatus(GameSession::STATUS_FINISHED);
        $em->flush();

        return $this->redirectToRoute('game_leaderboard', ['code' => $code]);
    }

    #[Route('/game/{code}/leaderboard', name: 'game_leaderboard', methods: ['GET'])]
    public function leaderboard(
        string $code,
        GameSessionRepository $sessionRepo,
        GameParticipantRepository $participantRepo
    ): Response {
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            throw $this->createNotFoundException('Session introuvable.');
        }

        $participants = $participantRepo->findBy(
            ['gameSession' => $session],
            ['totalScore' => 'DESC']
        );

        return $this->render('game_master/leaderboard.html.twig', [
            'session' => $session,
            'participants' => $participants,
        ]);
    }

    private function generateUniqueCode(GameSessionRepository $repo): string
    {
        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while ($repo->findOneBy(['code' => $code]) !== null);

        return $code;
    }
}
