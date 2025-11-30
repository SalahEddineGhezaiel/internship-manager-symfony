<?php

namespace App\Repository;

use App\Entity\OffreDeStage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OffreDeStage>
 */
class OffreDeStageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffreDeStage::class);
    }

    /**
     * @return OffreDeStage[] Returns an array of active OffreDeStage objects
     */
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

    //    public function findOneBySomeField($value): ?OffreDeStage
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
