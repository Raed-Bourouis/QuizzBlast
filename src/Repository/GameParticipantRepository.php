<?php

namespace App\Repository;

use App\Entity\GameParticipant;
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
}
