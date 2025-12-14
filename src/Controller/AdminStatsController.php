<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Entity\Entreprise;
use App\Entity\Etudiant;
use App\Entity\OffreDeStage;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminStatsController extends AbstractController
{
    #[Route('/admin/stats', name: 'admin_stats')]
    #[IsGranted('ROLE_ADMIN')]
    public function stats(EntityManagerInterface $em): Response
    {
        // KPIs
        $totalStudents = (int) $em->getRepository(Etudiant::class)->count([]);
        $totalCompanies = (int) $em->getRepository(Entreprise::class)->count([]);
        $totalOffers = (int) $em->getRepository(OffreDeStage::class)->count([]);
        $totalUsers = $totalStudents + $totalCompanies;

        $activeOffers = (int) $em->getRepository(OffreDeStage::class)->count(['estActive' => true]);
        $inactiveOffers = $totalOffers - $activeOffers;

        $pendingApps = (int) $em->getRepository(Candidature::class)->count(['statut' => 'EN_ATTENTE']);
        $acceptedApps = (int) $em->getRepository(Candidature::class)->count(['statut' => 'ACCEPTEE']);
        $rejectedApps = (int) $em->getRepository(Candidature::class)->count(['statut' => 'REFUSEE']);

        // Leaderboards
        $topCompanies = $em->createQueryBuilder()
            ->select('e as entreprise, COUNT(o.id) as nbOffres')
            ->from(Entreprise::class, 'e')
            ->leftJoin(OffreDeStage::class, 'o', 'WITH', 'o.entreprise = e')
            ->groupBy('e.id')
            ->orderBy('nbOffres', 'DESC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        $topOffers = $em->createQueryBuilder()
            ->select('o as offre, COUNT(c.id) as nbCandidatures')
            ->from(OffreDeStage::class, 'o')
            ->leftJoin(Candidature::class, 'c', 'WITH', 'c.offreDeStage = o')
            ->groupBy('o.id')
            ->orderBy('nbCandidatures', 'DESC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        // Trends: last 6 months counts
        $now = new DateTimeImmutable('first day of this month 00:00:00');
        $start = $now->sub(new DateInterval('P5M'));
        $period = new DatePeriod($start, new DateInterval('P1M'), 6);
        $labels = [];
        $offersSeries = [];
        $appsSeries = [];
        foreach ($period as $monthStart) {
            $monthEnd = $monthStart->add(new DateInterval('P1M'));
            $labels[] = $monthStart->format('M Y');

            $offersCount = (int) $em->createQueryBuilder()
                ->select('COUNT(o.id)')
                ->from(OffreDeStage::class, 'o')
                ->where('o.dateCreation >= :start AND o.dateCreation < :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()->getSingleScalarResult();
            $offersSeries[] = $offersCount;

            $appsCount = (int) $em->createQueryBuilder()
                ->select('COUNT(c.id)')
                ->from(Candidature::class, 'c')
                ->where('c.dateSoumission >= :start AND c.dateSoumission < :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
                ->getQuery()->getSingleScalarResult();
            $appsSeries[] = $appsCount;
        }

        return $this->render('admin/stats/index.html.twig', [
            'kpis' => [
                'totalUsers' => $totalUsers,
                'totalStudents' => $totalStudents,
                'totalCompanies' => $totalCompanies,
                'totalOffers' => $totalOffers,
                'activeOffers' => $activeOffers,
                'inactiveOffers' => $inactiveOffers,
                'pendingApps' => $pendingApps,
                'acceptedApps' => $acceptedApps,
                'rejectedApps' => $rejectedApps,
            ],
            'topCompanies' => $topCompanies,
            'topOffers' => $topOffers,
            'labels' => $labels,
            'offersSeries' => $offersSeries,
            'appsSeries' => $appsSeries,
        ]);
    }
}
