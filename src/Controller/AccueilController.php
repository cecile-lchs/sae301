<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function index(): Response
    {
        return $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
        ]);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentions(): Response
    {
        return $this->render('accueil/mentions_legales.html.twig');
    }

    #[Route('/politique-cookies', name: 'app_politique_cookies')]
    public function cookies(): Response
    {
        return $this->render('accueil/politique_cookies.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'app_politique_confidentialite')]
    public function confidentialite(): Response
    {
        return $this->render('accueil/politique_confidentialite.html.twig');
    }

    #[Route('/plan-du-site', name: 'app_plan_du_site')]
    public function planDuSite(): Response
    {
        return $this->render('accueil/plan_du_site.html.twig');
    }
}
