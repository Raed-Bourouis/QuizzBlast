<?php

namespace App\Repository;

use App\Entity\GameParticipant;
use App\Entity\GameSession;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameParticipant>
 */
class GameParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameParticipant::class);
    }

    public function findOneBySessionAndUser(GameSession $session, User $user): ?GameParticipant
    {
        return $this->createQueryBuilder('gp')
            ->andWhere('gp.gameSession = :session')
            ->andWhere('gp.user = :user')
            ->setParameter('session', $session)
            ->setParameter('user', $user)
            ->getQuery()  // ← ADD THIS LINE
            ->getOneOrNullResult();
    }
}
