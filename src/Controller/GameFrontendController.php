<?php

namespace App\Controller;

use App\Entity\GameSession;
use App\Repository\GameParticipantRepository;
use App\Repository\GameSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game')]
class GameFrontendController extends AbstractController
{
    public function __construct(
        private GameSessionRepository $sessionRepo,
        private GameParticipantRepository $participantRepo,
    ) {}

    #[Route('/join', name: 'game_join_page', methods: ['GET'])]
    public function joinPage(): Response
    {
        return $this->render('game/join.html.twig');
    }

    #[Route('/{code}/lobby', name: 'game_lobby', methods: ['GET'])]
    public function lobby(string $code): Response
    {
        $session = $this->sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }
        
        return $this->render('game/lobby.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/{code}/play', name: 'game_play', methods: ['GET'])]
    public function play(string $code): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $session = $this->sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }
        
        if ($session->getStatus() !== GameSession::STATUS_IN_PROGRESS) {
            return $this->redirectToRoute('game_lobby', ['code' => $code]);
        }
        
        $participant = $this->participantRepo->findOneBySessionAndUser($session, $user);
        if (!$participant) {
            throw $this->createAccessDeniedException('You are not a participant in this game');
        }
        
        return $this->render('game/play.html.twig', [
            'session' => $session,
            'participant' => $participant,
        ]);
    }

    #[Route('/{code}/leaderboard', name: 'game_leaderboard', methods: ['GET'])]
    public function leaderboard(string $code): Response
    {
        $session = $this->sessionRepo->findOneBy(['code' => $code]);
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }
        
        $participants = $this->participantRepo->findBy(
            ['gameSession' => $session],
            ['totalScore' => 'DESC']
        );
        
        return $this->render('game/leaderboard.html.twig', [
            'session' => $session,
            'participants' => $participants,
        ]);
    }
}
