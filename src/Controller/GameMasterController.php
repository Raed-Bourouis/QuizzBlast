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
use WebSocket\Client;

class GameMasterController extends AbstractController
{
    /**
     * Démarrer une partie à partir d'un quiz
     */
    #[Route('/game/start/{id}', name: 'game_host_create', methods: ['GET'])]
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) throw $this->createNotFoundException('Session introuvable');

        // Vérifier que l'utilisateur est l'hôte
        if ($session->getHost() !== $this->getUser()) {
            return $this->json(['error' => 'Vous n’êtes pas l’hôte de cette session'], 403);
        }

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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) throw $this->createNotFoundException('Session introuvable');

        // Vérifier que l'utilisateur est l'hôte
        if ($session->getHost() !== $this->getUser()) {
            return $this->json(['error' => 'Vous n’êtes pas l’hôte de cette session'], 403);
        }

        $totalQuestions = count($session->getQuiz()->getQuestions());
        $currentIndex = $session->getCurrentQuestionIndex();

        if ($currentIndex + 1 >= $totalQuestions) {
            return $this->json(['error' => 'Dernière question atteinte'], 400);
        }

        $session->setCurrentQuestionIndex($currentIndex + 1);
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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) throw $this->createNotFoundException('Session introuvable');

        // Vérifier que l'utilisateur est l'hôte
        if ($session->getHost() !== $this->getUser()) {
            return $this->json(['error' => 'Vous n’êtes pas l’hôte de cette session'], 403);
        }

        $participants = $participantRepo->findBy(['gameSession' => $session]);

        foreach ($participants as $participant) {
            $totalPoints = 0;
            foreach ($participant->getPlayerAnswers() ?? [] as $answer) {
                $totalPoints += $answer->getPoints() ?? 0;
            }
            $participant->setTotalScore($totalPoints);
        }

        $session->setStatus(GameSession::STATUS_FINISHED);
        $em->flush();

        $leaderboard = [];
        foreach ($participants as $p) {
            $leaderboard[] = [
                'nickname' => $p->getNickname() ?? 'Joueur',
                'score' => $p->getTotalScore() ?? 0
            ];
        }

        $this->sendWebSocket([
            'type' => 'END_GAME',
            'payload' => [
                'leaderboard' => $leaderboard
            ]
        ]);

        return $this->json(['success' => true]);
    }

    /**
     * Méthode interne pour envoyer des messages via WebSocket Ratchet
     */
    private function sendWebSocket(array $data): bool
    {
        try {
            $client = new Client("ws://localhost:8080/game");
            $client->send(json_encode($data));
            $client->close();
            return true;
        } catch (\Exception $e) {
            // On peut logger l'erreur
            error_log("[WebSocket ERROR] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère un code unique à 6 chiffres pour limiter les collisions
     */
    private function generateUniqueCode(GameSessionRepository $repo): string
    {
        $attempts = 0;
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $attempts++;
            if ($attempts > 10000) {
                throw new \RuntimeException('Impossible de générer un code unique');
            }
        } while ($repo->findOneBy(['code' => $code]) !== null);

        return $code;
    }
}
