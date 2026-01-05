<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class Article1Controller extends AbstractController
{
    #[Route('/article1', name: 'app_article1')]
    public function index(): Response
    {
        return $this->render('article1/index.html.twig', [
            'controller_name' => 'Article1Controller',
        ]);
    }
}
