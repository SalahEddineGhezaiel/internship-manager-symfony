<?php

namespace App\Repository;

use App\Entity\OffreDeStage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class OffreDeStageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffreDeStage::class);
    }

    
    public function findActiveOffres(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.estActive = :active')
            ->setParameter('active', true)
            ->leftJoin('o.entreprise', 'e')
            ->addSelect('e')
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    
}
