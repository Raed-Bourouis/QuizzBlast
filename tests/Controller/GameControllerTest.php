<?php

namespace App\Tests\Controller;

use App\Entity\Answer;
use App\Entity\GameParticipant;
use App\Entity\GameSession;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GameControllerTest extends WebTestCase
{
    public function testGetCurrentQuestionReturnsQuestion(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Create test data
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setIsVerified(true);
        $entityManager->persist($user);

        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz');
        $quiz->setDescription('Test Description');
        $quiz->setCreator($user);
        $quiz->setIsPublic(true);
        $entityManager->persist($quiz);

        $question = new Question();
        $question->setQuestionType(Question::TYPE_SINGLE_CHOICE);
        $question->setText('What is 2+2?');
        $question->setPoints(10);
        $question->setTimeLimit(30);
        $question->setQuiz($quiz);
        $entityManager->persist($question);

        $answer1 = new Answer();
        $answer1->setText('3');
        $answer1->setIsCorrect(false);
        $answer1->setOrderIndex(0);
        $answer1->setQuestion($question);
        $entityManager->persist($answer1);

        $answer2 = new Answer();
        $answer2->setText('4');
        $answer2->setIsCorrect(true);
        $answer2->setOrderIndex(1);
        $answer2->setQuestion($question);
        $entityManager->persist($answer2);

        $quiz->addQuestion($question);

        $session = new GameSession();
        $session->setCode('TEST01');
        $session->setStatus('IN_PROGRESS');
        $session->setQuiz($quiz);
        $session->setHost($user);
        $session->setCurrentQuestionIndex(0);
        $entityManager->persist($session);

        $entityManager->flush();

        // Make request to API
        $client->request('GET', '/api/game/session/' . $session->getId() . '/question');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('question', $responseData);
        $this->assertArrayHasKey('currentIndex', $responseData);
        $this->assertArrayHasKey('totalQuestions', $responseData);

        $this->assertEquals('What is 2+2?', $responseData['question']['text']);
        $this->assertEquals(10, $responseData['question']['points']);
        $this->assertEquals(30, $responseData['question']['timeLimit']);
        $this->assertCount(2, $responseData['question']['answers']);
        $this->assertEquals(0, $responseData['currentIndex']);
        $this->assertEquals(1, $responseData['totalQuestions']);
    }

    public function testGetCurrentQuestionReturns404WhenSessionNotFound(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/game/session/99999/question');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Session not found', $responseData['error']);
    }

    public function testGetCurrentQuestionReturns404WhenNoMoreQuestions(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();

        // Create test data with session past all questions
        $user = new User();
        $user->setEmail('test2@example.com');
        $user->setPassword('password');
        $user->setIsVerified(true);
        $entityManager->persist($user);

        $quiz = new Quiz();
        $quiz->setTitle('Test Quiz 2');
        $quiz->setDescription('Test Description');
        $quiz->setCreator($user);
        $quiz->setIsPublic(true);
        $entityManager->persist($quiz);

        $question = new Question();
        $question->setQuestionType(Question::TYPE_SINGLE_CHOICE);
        $question->setText('Test question');
        $question->setPoints(10);
        $question->setTimeLimit(30);
        $question->setQuiz($quiz);
        $entityManager->persist($question);

        $answer = new Answer();
        $answer->setText('Answer');
        $answer->setIsCorrect(true);
        $answer->setOrderIndex(0);
        $answer->setQuestion($question);
        $entityManager->persist($answer);

        $quiz->addQuestion($question);

        $session = new GameSession();
        $session->setCode('TEST02');
        $session->setStatus('IN_PROGRESS');
        $session->setQuiz($quiz);
        $session->setHost($user);
        $session->setCurrentQuestionIndex(999); // Past all questions
        $entityManager->persist($session);

        $entityManager->flush();

        // Make request to API
        $client->request('GET', '/api/game/session/' . $session->getId() . '/question');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('No more questions', $responseData['error']);
    }
}
