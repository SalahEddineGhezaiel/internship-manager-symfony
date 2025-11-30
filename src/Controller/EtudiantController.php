<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Form\EtudiantRegistrationType;
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
            // Hash the password
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($etudiant, $plainPassword);
            $etudiant->setMotDePasse($hashedPassword);

            // Set default role
            $etudiant->setRoles(['ROLE_ETUDIANT']);

            // Persist to database
            $entityManager->persist($etudiant);
            $entityManager->flush();

            // Add success message and redirect to login
            $this->addFlash('success', 'Inscription réussie! Veuillez vous connecter.');

            return $this->redirectToRoute('login');
        }

        return $this->render('etudiant/register.html.twig', [
            'form' => $form,
        ]);
    }
}
