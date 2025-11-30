<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\OffreDeStage;
use App\Form\EntrepriseRegistrationType;
use App\Form\OffreDeStageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EntrepriseController extends AbstractController
{
    #[Route('/entreprise/dashboard', name: 'entreprise_dashboard')]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function dashboard(): Response
    {
        $entreprise = $this->getUser();

        return $this->render('entreprise/dashboard.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/entreprise/register', name: 'entreprise_register')]
    #[IsGranted('ROLE_ADMIN')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $entreprise = new Entreprise();
        $form = $this->createForm(EntrepriseRegistrationType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($entreprise, $plainPassword);
            $entreprise->setMotDePasse($hashedPassword);

            // Set default role
            $entreprise->setRoles(['ROLE_ENTREPRISE']);

            // Persist to database
            $entityManager->persist($entreprise);
            $entityManager->flush();

            // Add success message and redirect to login
            $this->addFlash('success', 'Inscription réussie! Veuillez vous connecter.');

            return $this->redirectToRoute('login');
        }

        return $this->render('entreprise/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/entreprise/offres', name: 'entreprise_offres_list')]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function listOffres(): Response
    {
        $entreprise = $this->getUser();
        $offres = $entreprise->getOffreDeStages();

        return $this->render('entreprise/offres/list.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/entreprise/offre/new', name: 'entreprise_offre_new')]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function newOffre(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $offre = new OffreDeStage();
        $form = $this->createForm(OffreDeStageType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Link offer to logged-in company
            $offre->setEntreprise($this->getUser());

            $entityManager->persist($offre);
            $entityManager->flush();

            $this->addFlash('success', 'Offre de stage créée avec succès!');

            return $this->redirectToRoute('entreprise_offres_list');
        }

        return $this->render('entreprise/offres/form.html.twig', [
            'form' => $form,
            'title' => 'Créer une nouvelle offre',
        ]);
    }

    #[Route('/entreprise/offre/{id}/edit', name: 'entreprise_offre_edit')]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function editOffre(
        OffreDeStage $offre,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if offer belongs to logged-in company
        if ($offre->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette offre.');
        }

        $form = $this->createForm(OffreDeStageType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Offre de stage mise à jour avec succès!');

            return $this->redirectToRoute('entreprise_offres_list');
        }

        return $this->render('entreprise/offres/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier l\'offre',
            'offre' => $offre,
        ]);
    }

    #[Route('/entreprise/offre/{id}/delete', name: 'entreprise_offre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function deleteOffre(
        OffreDeStage $offre,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        // Check if offer belongs to logged-in company
        if ($offre->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette offre.');
        }

        // Verify CSRF token
        if ($this->isCsrfTokenValid('delete' . $offre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($offre);
            $entityManager->flush();

            $this->addFlash('success', 'Offre de stage supprimée avec succès!');
        }

        return $this->redirectToRoute('entreprise_offres_list');
    }

    #[Route('/entreprise/offre/{id}/toggle', name: 'entreprise_offre_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function toggleOffre(
        OffreDeStage $offre,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        // Check if offer belongs to logged-in company
        if ($offre->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette offre.');
        }

        // Verify CSRF token
        if ($this->isCsrfTokenValid('toggle' . $offre->getId(), $request->request->get('_token'))) {
            $offre->setEstActive(!$offre->isEstActive());
            $entityManager->flush();

            $status = $offre->isEstActive() ? 'activée' : 'désactivée';
            $this->addFlash('success', 'Offre de stage ' . $status . ' avec succès!');
        }

        return $this->redirectToRoute('entreprise_offres_list');
    }
}
