<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\OffreDeStage;
use App\Form\EtudiantRegistrationType;
use App\Form\CandidatureType;
use App\Entity\Candidature;
use App\Form\ChangePasswordType;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $etudiant = $this->getUser();

        $repo = $entityManager->getRepository(Candidature::class);
        $total = $repo->count(['etudiant' => $etudiant]);
        $enAttente = $repo->count(['etudiant' => $etudiant, 'statut' => 'EN_ATTENTE']);
        $acceptees = $repo->count(['etudiant' => $etudiant, 'statut' => 'ACCEPTEE']);
        $refusees = $repo->count(['etudiant' => $etudiant, 'statut' => 'REFUSEE']);

        return $this->render('etudiant/dashboard.html.twig', [
            'etudiant' => $etudiant,
            'totalCandidatures' => $total,
            'enAttente' => $enAttente,
            'acceptees' => $acceptees,
            'refusees' => $refusees,
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
    public function listOffres(OffreDeStageRepository $offreRepository, EntityManagerInterface $entityManager): Response
    {
        $offres = $offreRepository->findActiveOffres();
        $etudiant = $this->getUser();
        $applied = $entityManager->getRepository(Candidature::class)->findBy(['etudiant' => $etudiant]);
        $appliedOffreIds = array_map(fn($c) => $c->getOffreDeStage()->getId(), $applied);

        return $this->render('etudiant/offres/list.html.twig', [
            'offres' => $offres,
            'appliedOffreIds' => $appliedOffreIds,
        ]);
    }

    #[Route('/etudiant/offre/{id}', name: 'etudiant_offre_show')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function showOffre(OffreDeStage $offre, EntityManagerInterface $entityManager): Response
    {
        if (!$offre->isEstActive()) {
            throw $this->createNotFoundException('Cette offre n\'est pas disponible.');
        }

        $etudiant = $this->getUser();
        $hasApplied = (bool) $entityManager->getRepository(Candidature::class)->findOneBy([
            'etudiant' => $etudiant,
            'offreDeStage' => $offre,
        ]);

        return $this->render('etudiant/offres/show.html.twig', [
            'offre' => $offre,
            'hasApplied' => $hasApplied,
        ]);
    }

    #[Route('/etudiant/candidatures', name: 'etudiant_candidatures')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function mesCandidatures(Request $request, EntityManagerInterface $entityManager): Response
    {
        $etudiant = $this->getUser();
        $repo = $entityManager->getRepository(Candidature::class);

        $allowed = [null, 'EN_ATTENTE', 'ACCEPTEE', 'REFUSEE'];
        $statut = $request->query->get('statut');
        if ($statut === '') { $statut = null; }
        if (!in_array($statut, $allowed, true)) { $statut = null; }

        $criteria = ['etudiant' => $etudiant];
        if ($statut) {
            $criteria['statut'] = $statut;
        }
        $candidatures = $repo->findBy($criteria, ['dateSoumission' => 'DESC']);

        $total = $repo->count(['etudiant' => $etudiant]);
        $enAttente = $repo->count(['etudiant' => $etudiant, 'statut' => 'EN_ATTENTE']);
        $acceptees = $repo->count(['etudiant' => $etudiant, 'statut' => 'ACCEPTEE']);
        $refusees = $repo->count(['etudiant' => $etudiant, 'statut' => 'REFUSEE']);

        return $this->render('etudiant/candidatures/list.html.twig', [
            'candidatures' => $candidatures,
            'filterStatut' => $statut,
            'total' => $total,
            'enAttente' => $enAttente,
            'acceptees' => $acceptees,
            'refusees' => $refusees,
        ]);
    }

    #[Route('/etudiant/profil', name: 'etudiant_profil')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function profil(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $etudiant = $this->getUser();

        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $current = $passwordForm->get('currentPassword')->getData();
            if (!$passwordHasher->isPasswordValid($etudiant, $current)) {
                $this->addFlash('danger', 'Mot de passe actuel incorrect');
                return $this->redirectToRoute('etudiant_profil');
            }

            $new = $passwordForm->get('newPassword')->getData();
            $etudiant->setMotDePasse($passwordHasher->hashPassword($etudiant, $new));
            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour');
            return $this->redirectToRoute('etudiant_profil');
        }

        return $this->render('etudiant/profil/show.html.twig', [
            'etudiant' => $etudiant,
            'passwordForm' => $passwordForm,
        ]);
    }

    #[Route('/etudiant/candidature/{id}/annuler', name: 'etudiant_candidature_annuler', methods: ['POST'])]
    #[IsGranted('ROLE_ETUDIANT')]
    public function annulerCandidature(Candidature $candidature, Request $request, EntityManagerInterface $entityManager): Response
    {
        $etudiant = $this->getUser();
        if ($candidature->getEtudiant() !== $etudiant) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette candidature.");
        }

        if (!$this->isCsrfTokenValid('annuler'.$candidature->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('etudiant_candidatures');
        }

        if ($candidature->getStatut() !== 'EN_ATTENTE') {
            $this->addFlash('danger', 'Vous ne pouvez annuler qu\'une candidature en attente.');
            return $this->redirectToRoute('etudiant_candidatures');
        }

        // Delete uploaded CV file if present
        $relativePath = $candidature->getCvFichier();
        if ($relativePath) {
            $absolutePath = rtrim($this->getParameter('kernel.project_dir'), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
                . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        $entityManager->remove($candidature);
        $entityManager->flush();
        $this->addFlash('success', 'Votre candidature a été annulée.');

        return $this->redirectToRoute('etudiant_candidatures');
    }

    #[Route('/etudiant/profil/delete', name: 'etudiant_delete_account', methods: ['POST'])]
    #[IsGranted('ROLE_ETUDIANT')]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager): Response
    {
        $etudiant = $this->getUser();
        if (!$this->isCsrfTokenValid('delete_account'.$etudiant->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('etudiant_profil');
        }

        $this->container->get('security.token_storage')->setToken(null);
        $entityManager->remove($etudiant);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été supprimé.');
        return $this->redirectToRoute('login');
    }

    #[Route('/etudiant/offre/{id}/postuler', name: 'etudiant_offre_apply')]
    #[IsGranted('ROLE_ETUDIANT')]
    public function apply(
        OffreDeStage $offre,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // L'offre doit être active
        if (!$offre->isEstActive()) {
            throw $this->createNotFoundException("Cette offre n'est pas disponible.");
        }

        $etudiant = $this->getUser();

        // Empêcher la candidature en double pour la même offre
        $existing = $entityManager->getRepository(Candidature::class)->findOneBy([
            'etudiant' => $etudiant,
            'offreDeStage' => $offre,
        ]);
        if ($existing) {
            $this->addFlash('info', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('etudiant_offre_show', ['id' => $offre->getId()]);
        }

        $candidature = new Candidature();
        $form = $this->createForm(CandidatureType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $cvFile */
            $cvFile = $form->get('cvFichier')->getData();

            // Déterminer le répertoire de stockage (public/uploads/cv)
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $safeBaseName = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeBaseName = preg_replace('/[^A-Za-z0-9_-]/', '_', $safeBaseName) ?: 'cv';
            $newFilename = $safeBaseName . '_' . bin2hex(random_bytes(6)) . '.' . $cvFile->guessExtension();

            $cvFile->move($uploadDir, $newFilename);

            // Renseigner l'entité
            $candidature->setEtudiant($etudiant);
            $candidature->setOffreDeStage($offre);
            $candidature->setCvFichier('uploads/cv/' . $newFilename);
            // lettreMotivation déjà mappée par le formulaire

            $entityManager->persist($candidature);
            $entityManager->flush();

            $this->addFlash('success', 'Votre candidature a été soumise avec succès.');
            return $this->redirectToRoute('etudiant_offre_show', ['id' => $offre->getId()]);
        }

        return $this->render('etudiant/candidatures/apply.html.twig', [
            'offre' => $offre,
            'form' => $form,
        ]);
    }
}
