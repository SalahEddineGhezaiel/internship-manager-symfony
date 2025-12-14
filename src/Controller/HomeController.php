<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('visiteur_home');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_dashboard');
        } elseif (in_array('ROLE_ENTREPRISE', $user->getRoles())) {
            return $this->redirectToRoute('entreprise_dashboard');
        } elseif (in_array('ROLE_ETUDIANT', $user->getRoles())) {
            return $this->redirectToRoute('etudiant_dashboard');
        }

        
        return $this->redirectToRoute('login');
    }
}
