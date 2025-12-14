<?php

namespace App\Controller;

use App\Entity\OffreDeStage;
use App\Repository\OffreDeStageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VisiteurController extends AbstractController
{
    #[Route('/visiteur', name: 'visiteur_home')]
    public function index(OffreDeStageRepository $offreRepository): Response
    {
        $offres = $offreRepository->findActiveOffres();
        return $this->render('visiteur/index.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/visiteur/offre/{id}', name: 'visiteur_offre_show')]
    public function showOffre(OffreDeStage $offre): Response
    {
        if (!$offre->isEstActive()) {
            throw $this->createNotFoundException("Cette offre n'est pas disponible.");
        }
        return $this->render('visiteur/offres/show.html.twig', [
            'offre' => $offre,
        ]);
    }
}
