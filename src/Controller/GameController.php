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
        private EntityManagerInterface    $em,
        private GameSessionRepository     $sessionRepo,
        private GameParticipantRepository $participantRepo,
        private PlayerAnswerRepository    $playerAnswerRepo,
    )
    {
    }

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

    #[Route('/session/{id}/start', name: 'game_session_start', methods: ['POST'])]
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

    #[Route('/session/{id}/next', name: 'game_session_next', methods: ['POST'])]
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

    #[Route('/session/{id}/end', name: 'game_session_end', methods: ['POST'])]
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
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $participant = $this->participantRepo->find($participantId);
        if (!$participant) {
            return $this->json(['error' => 'Participant not found'], 404);
        }

        $session = $participant->getGameSession();
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        // Validate session is in progress
        if ($session->getStatus() !== GameSession::STATUS_IN_PROGRESS) {
            return $this->json(['error' => 'Session is not in progress'], 400);
        }

        $question = $this->em->getRepository(Question::class)->find($questionId);
        if (!$question) {
            return $this->json(['error' => 'Question not found'], 404);
        }

        // Validate this is the current question
        $questions = $session->getQuiz()->getQuestions()->toArray();
        $currentQuestion = $questions[$session->getCurrentQuestionIndex()] ?? null;
        if (!$currentQuestion || $currentQuestion->getId() !== $question->getId()) {
            return $this->json(['error' => 'Not the current question'], 400);
        }

        // Check if already answered this question
        $existingAnswer = $this->playerAnswerRepo->findOneBy([
            'gameParticipant' => $participant,
            'question' => $question
        ]);
        if ($existingAnswer) {
            return $this->json(['error' => 'Already answered this question'], 400);
        }

        $answer = $this->em->getRepository(Answer::class)->find($answerId);
        if (!$answer) {
            return $this->json(['error' => 'Answer not found'], 404);
        }

        // Validate answer belongs to question
        if ($answer->getQuestion()->getId() !== $question->getId()) {
            return $this->json(['error' => 'Answer does not belong to this question'], 400);
        }

        $isCorrect = $answer->isCorrect();
        $points = 0;

        if ($isCorrect) {
            $points = $question->getPoints();

            // Time bonus: faster answers get more points (max 20% bonus)
            $timeLimit = $question->getTimeLimit() * 1000; // Convert to ms
            if ($timeMs < $timeLimit) {
                $timeBonus = (1 - ($timeMs / $timeLimit)) * 0.2;
                $points = (int)($points * (1 + $timeBonus));
            }
        }

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

    #[Route('/session/{id}/question', name: 'game_get_question', methods: ['GET'])]
    public function getCurrentQuestion(int $id): JsonResponse
    {
        $session = $this->sessionRepo->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        $questions = $session->getQuiz()->getQuestions()->getValues();
        $currentIndex = $session->getCurrentQuestionIndex();

        if ($currentIndex >= count($questions)) {
            return $this->json(['error' => 'No more questions'], 404);
        }

        $question = $questions[$currentIndex];

        // Serialize question with answers
        $questionData = [
            'id' => $question->getId(),
            'text' => $question->getText(),
            'questionType' => $question->getQuestionType(),
            'points' => $question->getPoints(),
            'timeLimit' => $question->getTimeLimit(),
            'mediaUrl' => $question->getMediaUrl(),
            'answers' => []
        ];

        foreach ($question->getAnswers() as $answer) {
            $questionData['answers'][] = [
                'id' => $answer->getId(),
                'text' => $answer->getText(),
                'orderIndex' => $answer->getOrderIndex()
            ];
        }

        return $this->json([
            'question' => $questionData,
            'currentIndex' => $currentIndex,
            'totalQuestions' => count($questions)
        ]);
    }
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
