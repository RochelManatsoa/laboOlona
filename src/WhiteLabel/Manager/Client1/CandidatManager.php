<?php

namespace App\WhiteLabel\Manager\Client1;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class CandidatManager
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
    ) {
        $this->entityManager = $managerRegistry->getManager('client1');
    }
}
