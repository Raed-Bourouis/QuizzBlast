<?php

namespace App\Controller;

use App\Entity\GameSession;
use App\Repository\GameSessionRepository;
use App\Repository\GameParticipantRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameMasterController extends AbstractController
{
    /**
     * Démarrer une partie à partir d'un quiz
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
        if (!$quiz) throw $this->createNotFoundException('Quiz introuvable');

        $code = $this->generateUniqueCode($sessionRepo);

        $session = new GameSession();
        $session->setQuiz($quiz)
                ->setHost($this->getUser())
                ->setCode($code)
                ->setStatus(GameSession::STATUS_WAITING)
                ->setCurrentQuestionIndex(0);

        $em->persist($session);
        $em->flush();

        return $this->render('game_master/host.html.twig', [
            'session' => $session,
        ]);
    }

    /**
     * Lancer la partie et notifier tous les joueurs via WebSocket
     */
    #[Route('/game/{code}/start-game', name: 'game_start_game', methods: ['POST'])]
    public function startGame(string $code, GameSessionRepository $sessionRepo, EntityManagerInterface $em): Response
    {
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) throw $this->createNotFoundException('Session introuvable');

        $session->setStatus(GameSession::STATUS_IN_PROGRESS);
        $session->setCurrentQuestionIndex(0);
        $em->flush();

        $this->sendWebSocket([
            'type' => 'START_GAME',
            'payload' => [
                'code' => $code,
                'questionIndex' => 0
            ]
        ]);

        return $this->json(['success' => true]);
    }

    /**
     * Passer à la question suivante
     */
    #[Route('/game/{code}/next', name: 'game_next_question', methods: ['POST'])]
    public function nextQuestion(string $code, GameSessionRepository $sessionRepo, EntityManagerInterface $em): Response
    {
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) throw $this->createNotFoundException('Session introuvable');

        $session->setCurrentQuestionIndex($session->getCurrentQuestionIndex() + 1);
        $em->flush();

        $this->sendWebSocket([
            'type' => 'NEXT_QUESTION',
            'payload' => [
                'questionIndex' => $session->getCurrentQuestionIndex()
            ]
        ]);

        return $this->json(['success' => true]);
    }

    /**
     * Terminer la partie, calculer les scores et notifier via WebSocket
     */
    #[Route('/game/{code}/end', name: 'game_end', methods: ['POST'])]
    public function end(
        string $code,
        GameSessionRepository $sessionRepo,
        GameParticipantRepository $participantRepo,
        EntityManagerInterface $em
    ): Response {
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) throw $this->createNotFoundException('Session introuvable');

        $participants = $participantRepo->findBy(['gameSession' => $session]);

        foreach ($participants as $participant) {
            $totalPoints = 0;
            foreach ($participant->getPlayerAnswers() as $answer) {
                $totalPoints += $answer->getPoints() ?? 0;
            }
            $participant->setTotalScore($totalPoints);
        }

        $session->setStatus(GameSession::STATUS_FINISHED);
        $em->flush();

        // Envoi WebSocket END_GAME
        $this->sendWebSocket([
            'type' => 'END_GAME',
            'payload' => [
                'leaderboard' => array_map(fn($p)=>['nickname'=>$p->getNickname(),'score'=>$p->getTotalScore()], $participants)
            ]
        ]);

        return $this->json(['success' => true]);
    }

    /**
     * Méthode interne pour envoyer des messages WebSocket
     */
    private function sendWebSocket(array $data)
    {
        $conn = fsockopen("localhost", 8080, $errno, $errstr, 1);
        if (!$conn) return false;

        fwrite($conn, json_encode($data));
        fclose($conn);
        return true;
    }

    /**
     * Génère un code unique à 4 chiffres
     */
    private function generateUniqueCode(GameSessionRepository $repo): string
    {
        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while ($repo->findOneBy(['code' => $code]) !== null);

        return $code;
    }
}
