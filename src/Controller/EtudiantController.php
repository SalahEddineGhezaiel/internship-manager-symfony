<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\OffreDeStage;
use App\Form\EtudiantRegistrationType;
use App\Repository\OffreDeStageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EtudiantController extends AbstractController
{
    #[Route('/etudiant/dashboard', name: 'etudiant_dashboard')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function dashboard(): Response
    {
        $etudiant = $this->getUser();

        return $this->render('etudiant/dashboard.html.twig', [
            'etudiant' => $etudiant,
        ]);
    }

    #[Route('/etudiant/register', name: 'etudiant_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantRegistrationType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($etudiant, $plainPassword);
            $etudiant->setMotDePasse($hashedPassword);

            
            $etudiant->setRoles(['ROLE_ETUDIANT']);

            
            $entityManager->persist($etudiant);
            $entityManager->flush();

            
            $this->addFlash('success', 'Inscription réussie! Veuillez vous connecter.');

            return $this->redirectToRoute('login');
        }

        return $this->render('etudiant/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/etudiant/offres', name: 'etudiant_offres_list')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function listOffres(OffreDeStageRepository $offreRepository): Response
    {
        $offres = $offreRepository->findActiveOffres();

        return $this->render('etudiant/offres/list.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/etudiant/offre/{id}', name: 'etudiant_offre_show')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function showOffre(OffreDeStage $offre): Response
    {
        
        if (!$offre->isEstActive()) {
            throw $this->createNotFoundException('Cette offre n\'est pas disponible.');
        }

        return $this->render('etudiant/offres/show.html.twig', [
            'offre' => $offre,
        ]);
    }
}
