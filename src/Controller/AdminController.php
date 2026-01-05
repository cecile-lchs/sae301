<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AdminController extends AbstractController
{
#[Route('/admin', name: 'app_admin')]
public function login(AuthenticationUtils $auth): Response
{
return $this->render('admin/index.html.twig', [
'last_username' => $auth->getLastUsername(),
'error' => $auth->getLastAuthenticationError(),
]);
}

#[Route('/admin/dashboard', name: 'admin_dashboard')]
public function dashboard(ReservationRepository $reservationRepository): Response
{
$this->denyAccessUnlessGranted('ROLE_ADMIN');

$reservations = $reservationRepository->findBy([], ['dateCreation' => 'DESC'], 10);  // Trie par date décroissante et limite à 10

    $data = [];
    foreach ($reservations as $res) {

        $client = $res->getClient();
        $rdv = $res->getRendezVous();

        // Liste des services
        $services = [];
        foreach ($res->getService() as $service) {
            $services[] = $service->getNom();
        }

        $data[] = [
            'id' => $res->getId(),
            'nom' => $client ? $client->getPrenom().' '.$client->getNom() : '—',
            'date' => $rdv ? $rdv->getDate()->format('Y-m-d') : null,
            'service' => implode(', ', $services),
            'duree' => array_sum(
                array_map(fn($s) => $s->getDureeMinutes(), $res->getService()->toArray())
            ),
            'statut' => $res->getStatus(),
            'adresse' => $client?->getAdresse(),
            'lat' => $client?->getLatitude(),
            'lng' => $client?->getLongitude(),
            'heure_debut' => $rdv?->getHeureDebut()?->format('H:i'),
            'heure_fin' => $rdv?->getHeureFin()?->format('H:i'),
        ];
    }
return $this->render('admin/dashboard.html.twig', [
    'reservations'=> $data,
]);
}

//#[Route('/logout', name: 'app_logout')]
//public function logout(): void
//{
//// Symfony gère le logout automatiquement
//}
}
