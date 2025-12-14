<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Etudiant;
use App\Entity\Entreprise;
use App\Entity\OffreDeStage;
use App\Entity\Candidature;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdministrateurController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $admin = $this->getUser();

        $etudiants = (int) $entityManager->getRepository(Etudiant::class)->count([]);
        $entreprises = (int) $entityManager->getRepository(Entreprise::class)->count([]);
        $offres = (int) $entityManager->getRepository(OffreDeStage::class)->count([]);
        $utilisateurs = $etudiants + $entreprises; // exclude admins for now

        return $this->render('admin/dashboard.html.twig', [
            'admin' => $admin,
            'utilisateurs' => $utilisateurs,
            'etudiants' => $etudiants,
            'entreprises' => $entreprises,
            'offres' => $offres,
        ]);
    }

    #[Route('/admin/users', name: 'admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        $etudiants = $entityManager->getRepository(Etudiant::class)->findBy([], ['dateCreation' => 'DESC']);
        $entreprises = $entityManager->getRepository(Entreprise::class)->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('admin/users/list.html.twig', [
            'etudiants' => $etudiants,
            'entreprises' => $entreprises,
        ]);
    }

    #[Route('/admin/user/etudiant/{id}/delete', name: 'admin_user_etudiant_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteEtudiant(Etudiant $etudiant, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_etudiant'.$etudiant->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users');
        }

        // Guard: prevent deletion if candidatures exist
        if ($etudiant->getCandidatures()->count() > 0) {
            $this->addFlash('danger', "Impossible de supprimer l'étudiant: des candidatures existent.");
            return $this->redirectToRoute('admin_users');
        }

        $entityManager->remove($etudiant);
        $entityManager->flush();
        $this->addFlash('success', "Étudiant supprimé.");

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/user/entreprise/{id}/delete', name: 'admin_user_entreprise_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteEntreprise(Entreprise $entreprise, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_entreprise'.$entreprise->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_users');
        }

        // Guard: prevent deletion if offers exist
        if ($entreprise->getOffreDeStages()->count() > 0) {
            $this->addFlash('danger', "Impossible de supprimer l'entreprise: des offres existent.");
            return $this->redirectToRoute('admin_users');
        }

        $entityManager->remove($entreprise);
        $entityManager->flush();
        $this->addFlash('success', "Entreprise supprimée.");

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/offres', name: 'admin_offres')]
    #[IsGranted('ROLE_ADMIN')]
    public function offres(EntityManagerInterface $entityManager): Response
    {
        $offres = $entityManager->getRepository(OffreDeStage::class)->findBy([], ['dateCreation' => 'DESC']);
        return $this->render('admin/offres/list.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/admin/offre/{id}/toggle', name: 'admin_offre_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleOffreAdmin(OffreDeStage $offre, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_toggle' . $offre->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_offres');
        }
        $offre->setEstActive(!$offre->isEstActive());
        $entityManager->flush();
        $status = $offre->isEstActive() ? 'activée' : 'désactivée';
        $this->addFlash('success', 'Offre ' . $status . ' avec succès.');
        return $this->redirectToRoute('admin_offres');
    }

    #[Route('/admin/offre/{id}/delete', name: 'admin_offre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteOffreAdmin(OffreDeStage $offre, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_delete' . $offre->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_offres');
        }
        $entityManager->remove($offre);
        $entityManager->flush();
        $this->addFlash('success', 'Offre supprimée avec succès.');
        return $this->redirectToRoute('admin_offres');
    }
}
