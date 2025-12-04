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
     * Démarre une nouvelle partie à partir d'un quiz.
     *
     * URL : /game/start/{id}
     * - {id} = id du Quiz
     */
    #[Route('/game/start/{id}', name: 'game_start', methods: ['GET'])]
    public function start(
        int $id,
        QuizRepository $quizRepo,
        GameSessionRepository $sessionRepo,
        EntityManagerInterface $em
    ): Response {
        // On force un utilisateur connecté (Game Master)
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // 1) Récupérer le quiz
        $quiz = $quizRepo->find($id);
        if (!$quiz) {
            throw $this->createNotFoundException(
                'Quiz introuvable. Créez un quiz avant de démarrer une partie.'
            );
        }

        // 2) Générer un code de partie unique (4 chiffres)
        $code = $this->generateUniqueCode($sessionRepo);

        // 3) Créer la GameSession
        $session = new GameSession();
        $session->setQuiz($quiz);
        $session->setHost($this->getUser());
        $session->setCode($code);
        $session->setStatus(GameSession::STATUS_WAITING);
        $session->setCurrentQuestionIndex(0);

        $em->persist($session);
        $em->flush();

        // 4) Afficher la page Game Master avec le code
        return $this->render('game_master/host.html.twig', [
            'session' => $session,
        ]);
    }

    /**
     * Termine la partie : calcule les scores et passe le statut à FINISHED.
     *
     * URL : /game/{code}/end
     * - {code} = code à 4 chiffres de la GameSession
     */
    #[Route('/game/{code}/end', name: 'game_end', methods: ['GET'])]
    public function end(
        string $code,
        GameSessionRepository $sessionRepo,
        GameParticipantRepository $participantRepo,
        EntityManagerInterface $em
    ): Response {
        // 1) Retrouver la session grâce au code
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            throw $this->createNotFoundException('Session de jeu introuvable.');
        }

        // 2) Récupérer les participants de cette session
        $participants = $participantRepo->findBy(['gameSession' => $session]);

        // 3) Calculer le score pour chaque participant
        foreach ($participants as $participant) {
            $totalPoints = 0;

            // On utilise la relation GameParticipant -> PlayerAnswer
            foreach ($participant->getPlayerAnswers() as $answer) {
                // PlayerAnswer a bien getPoints()
                $points = $answer->getPoints() ?? 0;
                $totalPoints += $points;
            }

            // On enregistre le score total dans totalScore
            $participant->setTotalScore($totalPoints);
        }

        // 4) Marquer la session comme FINISHED
        $session->setStatus(GameSession::STATUS_FINISHED);
        $em->flush();

        // 5) Rediriger vers le leaderboard
        return $this->redirectToRoute('game_leaderboard', [
            'code' => $code,
        ]);
    }

    /**
     * Affiche le classement final pour une GameSession.
     *
     * URL : /game/{code}/leaderboard
     */
    #[Route('/game/{code}/leaderboard', name: 'game_leaderboard', methods: ['GET'])]
    public function leaderboard(
        string $code,
        GameSessionRepository $sessionRepo,
        GameParticipantRepository $participantRepo
    ): Response {
        $session = $sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            throw $this->createNotFoundException('Session de jeu introuvable.');
        }

        // Récupérer les participants triés par totalScore décroissant.
        $participants = $participantRepo->findBy(
            ['gameSession' => $session],
            ['totalScore' => 'DESC']
        );

        return $this->render('game_master/leaderboard.html.twig', [
            'session'      => $session,
            'participants' => $participants,
        ]);
    }

    /**
     * Génère un code à 4 chiffres unique pour une GameSession.
     */
    private function generateUniqueCode(GameSessionRepository $repo): string
    {
        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while ($repo->findOneBy(['code' => $code]) !== null);

        return $code;
    }
}
