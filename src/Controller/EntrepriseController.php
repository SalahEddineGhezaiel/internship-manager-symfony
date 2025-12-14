<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\OffreDeStage;
use App\Form\EntrepriseRegistrationType;
use App\Form\OffreDeStageType;
use App\Entity\Candidature;
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
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $entreprise = $this->getUser();
        $offres = $entreprise->getOffreDeStages();
        $offresActives = $offres->filter(fn($offre) => $offre->isEstActive())->count();

        // Compute candidature stats for all offers owned by this company
        $qb = $entityManager->createQueryBuilder();
        $totalCandidatures = (int) $qb->select('COUNT(c.id)')
            ->from('App\\Entity\\Candidature', 'c')
            ->join('c.offreDeStage', 'o')
            ->where('o.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()->getSingleScalarResult();

        $countByStatut = function (string $statut) use ($entityManager, $entreprise) {
            $qb2 = $entityManager->createQueryBuilder();
            return (int) $qb2->select('COUNT(c2.id)')
                ->from('App\\Entity\\Candidature', 'c2')
                ->join('c2.offreDeStage', 'o2')
                ->where('o2.entreprise = :entreprise')
                ->andWhere('c2.statut = :statut')
                ->setParameter('entreprise', $entreprise)
                ->setParameter('statut', $statut)
                ->getQuery()->getSingleScalarResult();
        };

        $enAttente = $countByStatut('EN_ATTENTE');
        $acceptees = $countByStatut('ACCEPTEE');
        $refusees = $countByStatut('REFUSEE');

        return $this->render('entreprise/dashboard.html.twig', [
            'entreprise' => $entreprise,
            'totalOffres' => $offres->count(),
            'offresActives' => $offresActives,
            'totalCandidatures' => $totalCandidatures,
            'enAttente' => $enAttente,
            'acceptees' => $acceptees,
            'refusees' => $refusees,
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
            
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($entreprise, $plainPassword);
            $entreprise->setMotDePasse($hashedPassword);

            
            $entreprise->setRoles(['ROLE_ENTREPRISE']);

            
            $entityManager->persist($entreprise);
            $entityManager->flush();

            
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
        
        if ($offre->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette offre.');
        }

        
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
        
        if ($offre->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette offre.');
        }

        
        if ($this->isCsrfTokenValid('toggle' . $offre->getId(), $request->request->get('_token'))) {
            $offre->setEstActive(!$offre->isEstActive());
            $entityManager->flush();

            $status = $offre->isEstActive() ? 'activée' : 'désactivée';
            $this->addFlash('success', 'Offre de stage ' . $status . ' avec succès!');
        }

        return $this->redirectToRoute('entreprise_offres_list');
    }

    #[Route('/entreprise/candidatures', name: 'entreprise_candidatures_list')]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function listCandidatures(EntityManagerInterface $entityManager): Response
    {
        $entreprise = $this->getUser();
        // All candidatures for offers of this company
        $qb = $entityManager->createQueryBuilder();
        $candidatures = $qb->select('c', 'o')
            ->from(Candidature::class, 'c')
            ->join('c.offreDeStage', 'o')
            ->where('o.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->orderBy('c.dateSoumission', 'DESC')
            ->getQuery()->getResult();

        return $this->render('entreprise/candidatures/list.html.twig', [
            'candidatures' => $candidatures,
        ]);
    }

    #[Route('/entreprise/offre/{id}/candidatures', name: 'entreprise_candidatures_by_offre')]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function listCandidaturesByOffre(OffreDeStage $offre, EntityManagerInterface $entityManager): Response
    {
        if ($offre->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette offre.");
        }

        $candidatures = $entityManager->getRepository(Candidature::class)->findBy(
            ['offreDeStage' => $offre],
            ['dateSoumission' => 'DESC']
        );

        return $this->render('entreprise/candidatures/list.html.twig', [
            'candidatures' => $candidatures,
            'offre' => $offre,
        ]);
    }

    #[Route('/entreprise/candidature/{id}', name: 'entreprise_candidature_show')]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function showCandidature(Candidature $candidature): Response
    {
        if ($candidature->getOffreDeStage()->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette candidature.");
        }

        return $this->render('entreprise/candidatures/show.html.twig', [
            'candidature' => $candidature,
        ]);
    }

    #[Route('/entreprise/candidature/{id}/statut', name: 'entreprise_candidature_update_statut', methods: ['POST'])]
    #[IsGranted('ROLE_ENTREPRISE')]
    public function updateCandidatureStatut(Candidature $candidature, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($candidature->getOffreDeStage()->getEntreprise() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette candidature.");
        }

        if (!$this->isCsrfTokenValid('statut'.$candidature->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('entreprise_candidature_show', ['id' => $candidature->getId()]);
        }

        $newStatut = $request->request->get('statut');
        $allowed = ['EN_ATTENTE', 'ACCEPTEE', 'REFUSEE'];
        if (!in_array($newStatut, $allowed, true)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirectToRoute('entreprise_candidature_show', ['id' => $candidature->getId()]);
        }

        $candidature->setStatut($newStatut);
        if ($newStatut !== 'EN_ATTENTE') {
            $candidature->setDateReponse(new \DateTimeImmutable());
        } else {
            $candidature->setDateReponse(null);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Statut de la candidature mis à jour.');

        return $this->redirectToRoute('entreprise_candidature_show', ['id' => $candidature->getId()]);
    }
}
