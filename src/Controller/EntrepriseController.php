<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Form\EntrepriseRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EntrepriseController extends AbstractController
{
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
}
