<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\GameParticipant;
use App\Entity\GameSession;
use App\Entity\PlayerAnswer;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Repository\GameParticipantRepository;
use App\Repository\GameSessionRepository;
use App\Repository\PlayerAnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/game')]
class GameController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameSessionRepository $sessionRepo,
        private GameParticipantRepository $participantRepo,
        private PlayerAnswerRepository $playerAnswerRepo,
    ) {}

    // ---------- UTIL ----------

    private function generateSessionCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $exists = $this->sessionRepo->findOneBy(['code' => $code]);
        } while ($exists !== null);

        return $code;
    }

    // ---------- DAY 1 : CREATE + JOIN ----------

    #[Route('/session/create/{quizId}', name: 'game_create_session', methods: ['POST'])]
    public function createSession(int $quizId): JsonResponse
    {
        $quiz = $this->em->getRepository(Quiz::class)->find($quizId);
        if (!$quiz) return $this->json(['error' => 'Quiz not found'], 404);

        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $session = new GameSession();
        $session->setQuiz($quiz);
        $session->setHost($user);
        $session->setStatus('WAITING');
        $session->setCode($this->generateSessionCode());

        $this->em->persist($session);

        // host devient le premier participant
        $participant = new GameParticipant();
        $participant->setGameSession($session);
        $participant->setUser($user);
        $participant->setNickname($user->getUserIdentifier()); // ou ->getUsername()
        $participant->setIsHost(true);

        $this->em->persist($participant);
        $this->em->flush();

        return $this->json([
            'sessionId' => $session->getId(),
            'code' => $session->getCode(),
        ]);
    }

    #[Route('/session/join/{code}', name: 'game_join_session', methods: ['POST'])]
    public function joinSession(string $code): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Unauthorized'], 401);

        $session = $this->sessionRepo->findOneBy(['code' => $code]);
        if (!$session) return $this->json(['error' => 'Session not found'], 404);

        if ($session->getStatus() !== 'WAITING') {
            return $this->json(['error' => 'Session already started'], 400);
        }

        // éviter de rejoindre 2 fois
        $existing = $this->participantRepo->findOneBySessionAndUser($session, $user);
        if ($existing) {
            return $this->json([
                'participantId' => $existing->getId(),
                'sessionId' => $session->getId(),
            ]);
        }

        $participant = new GameParticipant();
        $participant->setGameSession($session);
        $participant->setUser($user);
        $participant->setNickname($user->getUserIdentifier());

        $this->em->persist($participant);
        $this->em->flush();

        return $this->json([
            'participantId' => $participant->getId(),
            'sessionId' => $session->getId(),
        ]);
    }

    // ---------- DAY 2 : START / NEXT / END ----------

    #[Route('/session/{id}/start', name: 'game_start', methods: ['POST'])]
    public function start(int $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) return $this->json(['error' => 'Session not found'], 404);

        $session->setStatus('IN_PROGRESS');
        $session->setStartedAt(new \DateTimeImmutable());
        $session->setCurrentQuestionIndex(0);

        $this->em->flush();

        return $this->json(['status' => 'IN_PROGRESS']);
    }

    #[Route('/session/{id}/next', name: 'game_next', methods: ['POST'])]
    public function nextQuestion(int $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) return $this->json(['error' => 'Session not found'], 404);

        $session->setCurrentQuestionIndex($session->getCurrentQuestionIndex() + 1);
        $this->em->flush();

        return $this->json([
            'currentQuestionIndex' => $session->getCurrentQuestionIndex()
        ]);
    }

    #[Route('/session/{id}/end', name: 'game_end', methods: ['POST'])]
    public function end(int $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) return $this->json(['error' => 'Session not found'], 404);

        $session->setStatus('FINISHED');
        $session->setEndedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->json(['status' => 'FINISHED']);
    }

    // ---------- DAY 2 : SUBMIT ANSWER + SCORE ----------

    #[Route('/answer/submit', name: 'game_submit_answer', methods: ['POST'])]
    public function submitAnswer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $participantId = $data['participantId'] ?? null;
        $questionId = $data['questionId'] ?? null;
        $answerId = $data['answerId'] ?? null;
        $timeMs = $data['timeMs'] ?? 0;

        if (!$participantId || !$questionId || !$answerId) {
            return $this->json(['error' => 'Missing fields'], 400);
        }

        $participant = $this->participantRepo->find($participantId);
        if (!$participant) return $this->json(['error' => 'Participant not found'], 404);

        $question = $this->em->getRepository(Question::class)->find($questionId);
        $answer = $this->em->getRepository(Answer::class)->find($answerId);
        if (!$question || !$answer) return $this->json(['error' => 'Question/Answer not found'], 404);

        $isCorrect = $answer->getIsCorrect(); // adapte le nom du champ
        // exemple simple de scoring : 100 pts si correct - (timeMs/100)
        $points = $isCorrect ? max(0, 100 - intdiv($timeMs, 100)) : 0;

        $playerAnswer = new PlayerAnswer();
        $playerAnswer->setGameParticipant($participant);
        $playerAnswer->setQuestion($question);
        $playerAnswer->setSelectedAnswer($answer);
        $playerAnswer->setIsCorrect($isCorrect);
        $playerAnswer->setPoints($points);
        $playerAnswer->setTimeToAnswer($timeMs);

        if ($isCorrect) {
            $participant->addScore($points);
        }

        $this->em->persist($playerAnswer);
        $this->em->flush();

        return $this->json([
            'correct' => $isCorrect,
            'points' => $points,
            'totalScore' => $participant->getTotalScore(),
        ]);
    }

    // ---------- "REAL-TIME" PAR POLLING ----------

    #[Route('/session/{id}/state', name: 'game_state', methods: ['GET'])]
    public function state(int $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) return $this->json(['error' => 'Session not found'], 404);

        $participants = $this->participantRepo->findBy(['gameSession' => $session]);

        $players = [];
        foreach ($participants as $p) {
            $players[] = [
                'id' => $p->getId(),
                'nickname' => $p->getNickname(),
                'score' => $p->getTotalScore(),
                'isHost' => $p->isHost(),
            ];
        }

        return $this->json([
            'status' => $session->getStatus(),
            'currentQuestionIndex' => $session->getCurrentQuestionIndex(),
            'players' => $players,
        ]);
    }
}
