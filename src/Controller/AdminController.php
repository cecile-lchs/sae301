<?php

namespace App\Controller;

use App\Entity\Indisponibilite;
use App\Entity\Reservation;
use App\Form\IndisponibiliteType;
use App\Repository\ClientRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;


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

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank.');
    }

//
//#[Route('/admin/dashboard', name: 'admin_dashboard')]
//public function dashboard(ReservationRepository $reservationRepository): Response
//{
//$this->denyAccessUnlessGranted('ROLE_ADMIN');
//
//$reservations = $reservationRepository->findBy([], ['dateCreation' => 'DESC'], 10);  // Trie par date décroissante et limite à 10
//
//    $data = [];
//    foreach ($reservations as $res) {
//
//        $client = $res->getClient();
//        $rdv = $res->getRendezVous();
//
//        // Liste des services
//        $services = [];
//        foreach ($res->getService() as $service) {
//            $services[] = $service->getNom();
//        }
//
//        $data[] = [
//            'id' => $res->getId(),
//            'nom' => $client ? $client->getPrenom().' '.$client->getNom() : '—',
//            'date' => $rdv ? $rdv->getDate()->format('Y-m-d') : null,
//            'service' => implode(', ', $services),
//            'duree' => array_sum(
//                array_map(fn($s) => $s->getDureeMinutes(), $res->getService()->toArray())
//            ),
//            'statut' => $res->getStatus(),
//            'adresse' => $client?->getAdresse(),
//            'lat' => $client?->getLatitude(),
//            'lng' => $client?->getLongitude(),
//            'heure_debut' => $rdv?->getHeureDebut()?->format('H:i'),
//            'heure_fin' => $rdv?->getHeureFin()?->format('H:i'),
//        ];
//    }
//return $this->render('admin/dashboard.html.twig', [
//    'reservations'=> $data,
//]);
//}
//
//    #[Route('/admin/dashboard', name: 'admin_dashboard')]
//    public function dashboard(ReservationRepository $reservationRepository): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_ADMIN');
//
//        // --- 1️⃣ Nombre total de réservations et clients ---
//        $allReservations = $reservationRepository->findAll(); // toutes les réservations pour le compteur
//        $totalReservations = count($allReservations);
//
//        // Comptage des clients uniques
//        $clientsIds = [];
//        foreach ($allReservations as $res) {
//            $client = $res->getClient();
//            if ($client) {
//                $clientsIds[$client->getId()] = true;
//            }
//        }
//        $totalClients = count($clientsIds);
//
//        // --- 2️⃣ Récupération des 10 dernières réservations pour la table ---
//        $reservations = $reservationRepository->findBy([], ['dateCreation' => 'DESC'], 10);
//
//        $data = [];
//        foreach ($reservations as $res) {
//
//            $client = $res->getClient();
//            $rdv = $res->getRendezVous();
//
//            // Liste des services
//            $services = [];
//            foreach ($res->getService() as $service) {
//                $services[] = $service->getNom();
//            }
//
//            $data[] = [
//                'id' => $res->getId(),
//                'nom' => $client ? $client->getPrenom().' '.$client->getNom() : '—',
//                'date' => $rdv ? $rdv->getDate()->format('Y-m-d') : null,
//                'service' => implode(', ', $services),
//                'duree' => array_sum(
//                    array_map(fn($s) => $s->getDureeMinutes(), $res->getService()->toArray())
//                ),
//                'statut' => $res->getStatus(),
//                'adresse' => $client?->getAdresse(),
//                'lat' => $client?->getLatitude(),
//                'lng' => $client?->getLongitude(),
//                'heure_debut' => $rdv?->getHeureDebut()?->format('H:i'),
//                'heure_fin' => $rdv?->getHeureFin()?->format('H:i'),
//            ];
//        }
//
//        return $this->render('admin/dashboard.html.twig', [
//            'reservations'=> $data,          // Les 10 dernières
//            'totalReservations' => $totalReservations, // Compteur
//            'totalClients' => $totalClients, // Compteur clients uniques
//        ]);
//    }
//
//    #[Route('/admin/dashboard', name: 'admin_dashboard')]
//    public function dashboard(ReservationRepository $reservationRepository): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_ADMIN');
//
//        // Toutes les réservations pour compteur et calendrier
//        $allReservations = $reservationRepository->findAll();
//        $totalReservations = count($allReservations);
//
//        $clientsIds = [];
//        foreach ($allReservations as $res) {
//            $client = $res->getClient();
//            if($client) $clientsIds[$client->getId()] = true;
//        }
//        $totalClients = count($clientsIds);
//
//        // 10 dernières réservations pour la table
//        $lastReservations = $reservationRepository->findBy([], ['dateCreation'=>'DESC'], 10);
//
//        $reservationsData = [];
//        foreach($allReservations as $res){
//            $client = $res->getClient();
//            $rdv = $res->getRendezVous();
//
//            $services = [];
//            foreach($res->getService() as $service){
//                $services[] = $service->getNom();
//            }
//
//            $reservationsData[] = [
//                'id' => $res->getId(),
//                'nom' => $client ? $client->getPrenom().' '.$client->getNom() : '—',
//                'date' => $rdv ? $rdv->getDate()->format('Y-m-d') : null,
//                'service' => implode(', ', $services),
//                'duree' => array_sum(array_map(fn($s)=>$s->getDureeMinutes(), $res->getService()->toArray())),
//                'statut' => $res->getStatus(),
//                'adresse' => $client?->getAdresse(),
//                'lat' => $client?->getLatitude(),
//                'lng' => $client?->getLongitude(),
//                'heure_debut' => $rdv?->getHeureDebut()?->format('H:i'),
//                'heure_fin' => $rdv?->getHeureFin()?->format('H:i'),
//            ];
//        }
//
//        // On ne garde que les 10 dernières pour la table
//        $tableData = array_slice($reservationsData, 0, 10);
//
//        return $this->render('admin/dashboard.html.twig', [
//            'reservations' => $tableData,      // pour la liste
//            'allReservations' => $reservationsData, // pour le calendrier
//            'totalReservations' => $totalReservations,
//            'totalClients' => $totalClients,
//        ]);
//    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(ReservationRepository $reservationRepository, \App\Repository\IndisponibiliteRepository $indisponibiliteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // --- Toutes les réservations pour le calendrier et compteur ---
        $allReservations = $reservationRepository->findAll();
        $totalReservations = count($allReservations);

        // --- Indisponibilités ---
        $allIndisponibilites = $indisponibiliteRepository->findAll();
        $indisponibilitesData = [];
        foreach ($allIndisponibilites as $indispo) {
            $start = $indispo->getDebut();
            $end = $indispo->getFin();
            $motif = $indispo->getType();

            if ($start && $end) {
                // Convert to DateTime to ensure we can use modify()
                $current = \DateTime::createFromInterface($start);
                $endDt = \DateTime::createFromInterface($end);

                while ($current <= $endDt) {
                    $indisponibilitesData[] = [
                        'id' => $indispo->getId(),
                        'date' => $current->format('Y-m-d'),
                        'motif' => $motif,
                    ];
                    $current->modify('+1 day');
                }
            }
        }


        $clientsIds = [];
        foreach ($allReservations as $res) {
            $client = $res->getClient();
            if ($client)
                $clientsIds[$client->getId()] = true;
        }
        $totalClients = count($clientsIds);

        // --- Préparer les données pour JS ---
        $reservationsData = [];
        foreach ($allReservations as $res) {
            $client = $res->getClient();
            $rdv = $res->getRendezVous();

            $services = [];
            foreach ($res->getService() as $service) {
                $services[] = $service->getNom();
            }

            $reservationsData[] = [
                'id' => $res->getId(),
                'nom' => $client ? $client->getPrenom() . ' ' . $client->getNom() : '—',
                'date' => $rdv ? $rdv->getDate()->format('Y-m-d') : null,
                'service' => implode(', ', $services),
                'duree' => array_sum(array_map(fn($s) => $s->getDureeMinutes(), $res->getService()->toArray())),
                'statut' => $res->getStatus(),
                'adresse' => $client?->getAdresse(),
                'lat' => $client?->getLatitude(),
                'lng' => $client?->getLongitude(),
                'heure_debut' => $rdv?->getHeureDebut()?->format('H:i'),
                'heure_fin' => $rdv?->getHeureFin()?->format('H:i'),
            ];
        }

        // --- 7 dernières réservations pour la table ---
        $tableData = array_slice(array_reverse($reservationsData), 0, 7); // les plus récentes

        return $this->render('admin/dashboard.html.twig', [
            'reservations' => $tableData,        // pour le tableau
            'allReservations' => $reservationsData, // pour le calendrier et la carte
            'unavailabilities' => $indisponibilitesData, // pour le calendrier
            'totalReservations' => $totalReservations,
            'totalClients' => $totalClients,
        ]);
    }


//#[Route('/logout', name: 'app_logout')]
//public function logout(): void
//{
//// Symfony gère le logout automatiquement
//}

//________________________________________________________________________________________________________AUTRE

    #[Route('/admin/client', name: 'admin_client')]
    public function client(ClientRepository $clientRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/client.html.twig', [
            'clients' => $clientRepository->findAll(),
        ]);
    }


    #[Route('/admin/client/{id}', name: 'admin_client_show')]
    public function showClient(int $id, ClientRepository $clientRepository): Response
    {
        $client = $clientRepository->find($id);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé.');
        }

        return $this->render('admin/client_show.html.twig', [
            'client' => $client
        ]);
    }

//
//    #[Route('/admin/parametres', name: 'admin_parametres')]
//    public function parametres(Request $request, EntityManagerInterface $em): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_ADMIN');
//
//        $indisponibilite = new Indisponibilite();
//        $form = $this->createForm(IndisponibiliteType::class, $indisponibilite);
//
//        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
//            $em->persist($indisponibilite);
//            $em->flush();
//
//            $this->addFlash('success', 'Indisponibilité ajoutée !');
//
//            return $this->redirectToRoute('admin_parametres');
//        }
//
//        // Récupérer toutes les indisponibilités
//        $indisponibilites = $em->getRepository(Indisponibilite::class)->findBy([], ['debut' => 'ASC']);
//
//        return $this->render('admin/parametres.html.twig', [
//            'form' => $form->createView(),
//            'indisponibilites' => $indisponibilites,
//        ]);
//    }


    #[Route('/admin/parametres', name: 'admin_parametres')]
    public function parametres(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $indisponibilite = new Indisponibilite();
        $form = $this->createForm(IndisponibiliteType::class, $indisponibilite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($indisponibilite);
            $em->flush();

            $this->addFlash('success', 'Indisponibilité ajoutée !');
            return $this->redirectToRoute('admin_parametres');
        }

        // Ajouter automatiquement les jours fériés
        if ($request->isMethod('POST') && $request->request->get('auto_holidays')) {

            if (!$this->isCsrfTokenValid(
                'auto_holidays',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException();
            }

            $year = (int) date('Y');

            $holidays = [
                "Jour de l'An" => "$year-01-01",
                "Lundi de Pâques" => "$year-04-13",
                "Fête du Travail" => "$year-05-01",
                "Victoire 1945" => "$year-05-08",
                "Ascension" => "$year-05-21",
                "Lundi de Pentecôte" => "$year-06-01",
                "Fête Nationale" => "$year-07-14",
                "Assomption" => "$year-08-15",
                "Toussaint" => "$year-11-01",
                "Armistice" => "$year-11-11",
                "Noël" => "$year-12-25",
            ];

            foreach ($holidays as $name => $dateStr) {
                $date = new \DateTime($dateStr);
                $date->setTime(0, 0, 0);

                $exists = $em->getRepository(Indisponibilite::class)
                    ->findOneBy([
                        'debut' => $date,
                        'type' => 'Jour Férié'
                    ]);

                if (!$exists) {
                    $holiday = new Indisponibilite();
                    $holiday->setDebut($date);
                    $holiday->setFin($date);
                    $holiday->setType('Jour Férié');

                    $em->persist($holiday);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Jours fériés intégrés automatiquement');

            return $this->redirectToRoute('admin_parametres');
        }


        // --- Récupérer toutes les indisponibilités ---
        $indisponibilites = $em->getRepository(Indisponibilite::class)
            ->findBy([], ['debut' => 'ASC']);

        return $this->render('admin/parametres.html.twig', [
            'form' => $form->createView(),
            'indisponibilites' => $indisponibilites,
        ]);
    }

    #[Route('/admin/indisponibilite/{id}/delete', name: 'admin_indisponibilite_delete', methods: ['POST'])]
    public function deleteIndisponibilite(
        Indisponibilite $indisponibilite,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid(
            'delete_indisponibilite_' . $indisponibilite->getId(),
            $request->request->get('_token')
        )) {
            $em->remove($indisponibilite);
            $em->flush();

            $this->addFlash('success', 'Indisponibilité supprimée.');
        }

        return $this->redirectToRoute('admin_parametres');
    }



    #[Route('/reservation', name: 'admin_reservation')]
    public function reservation(ReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservationsData = [];
        $services = [];

        $allReservations = $reservationRepository->findBy([], ['dateCreation' => 'DESC']);

        foreach ($allReservations as $res) {
            $client = $res->getClient();
            $rdv = $res->getRendezVous();

            $servicesList = [];
            foreach ($res->getService() as $service) {
                $servicesList[] = $service->getNom();
                if (!in_array($service->getNom(), $services)) {
                    $services[] = $service->getNom();
                }
            }

            $reservationsData[] = [
                'id' => $res->getId(),
                'nom' => $client ? $client->getPrenom() . ' ' . $client->getNom() : '—',
                'description' => 'Description de la réservation...', // Placeholder or fetch real desc if distinct from service
                'date' => $rdv ? $rdv->getDate() : null,
                'service' => implode(', ', $servicesList),
                'heure_debut' => $rdv?->getHeureDebut()?->format('H:i'),
                'heure_fin' => $rdv?->getHeureFin()?->format('H:i'),

            ];
        }

        return $this->render('admin/reservation.html.twig', [
            'reservations' => $reservationsData,
            'services' => $services,
        ]);
    }

    #[Route('/reservation/{id}', name: 'admin_reservation_details')]
    public function reservationDetails(int $id, ReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $client = $reservation->getClient();
        $rdv = $reservation->getRendezVous();

        $servicesList = [];
        foreach ($reservation->getService() as $service) {
            $servicesList[] = $service->getNom();
        }

        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);

        $data = [
            'id' => $reservation->getId(),
            'nom' => $client ? $client->getPrenom() . ' ' . $client->getNom() : 'Client Inconnu',
            'email' => $client ? $client->getEmail() : '—',
            'telephone' => $client ? $client->getTelephone() : '—',
            'adresse' => $client ? $client->getAdresse() : '—',
            'service' => implode(', ', $servicesList),
            'date' => $rdv ? $rdv->getDate() : null,
            'dateFormatted' => $rdv ? $formatter->format($rdv->getDate()) : 'Date inconnue',
            'heure_debut' => $rdv?->getHeureDebut()?->format('H:i'),
            'heure_fin' => $rdv?->getHeureFin()?->format('H:i'),
            'description' => 'Description détaillée...',
            'status' => $reservation->getStatus(),
            'dateCreation' => $reservation->getDateCreation(),
        ];

        return $this->render('admin/reservation_details.html.twig', [
            'reservation' => $data
        ]);
    }
//    #[Route('/planning', name: 'admin_planning')]
//    public function planning(ReservationRepository $reservationRepository): Response
//    {
//        // Logic for Planning - potentially passing reservations as events
//        $reservations = $reservationRepository->findAll();
//        // Transform for JS if needed, similar to dashboard
//
//        return $this->render('admin/planning.html.twig', [
//            'reservations' => $reservations,
//        ]);
//    }

    #[Route('/planning', name: 'admin_planning')]
    public function planning(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findAll();

        $events = [];

        foreach ($reservations as $res) {
            $rdv = $res->getRendezVous();
            $client = $res->getClient();

            if (!$rdv || !$client) continue;

            $services = $res->getService()->map(fn($s) => $s->getNom())->toArray();
            $servicesStr = implode(', ', $services);

            $events[] = [
                'title' => $servicesStr ?: 'Service',
                'client' => $client->getPrenom() . ' ' . $client->getNom(),
                'time' => $rdv->getHeureDebut()->format('H:i') . ' - ' . $rdv->getHeureFin()->format('H:i'),
                'address' => $client->getAdresse(),
                'desc' => '',
                'year' => $rdv->getDate()->format('Y'),
                'month' => (int)$rdv->getDate()->format('n') - 1, // JS: 0-11
                'date' => (int)$rdv->getDate()->format('j'),
                'color' => 'primary',
            ];
        }

        return $this->render('admin/planning.html.twig', [
            'events' => $events,
        ]);
    }


#[Route('/reservation/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    public function deleteReservation(
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'La réservation a bien été supprimée.');

        return $this->redirectToRoute('admin_reservation');
    }
    #[Route('/reservation/{id}/edit', name: 'admin_reservation_edit')]
    public function editReservation(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(\App\Form\ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Réservation modifiée avec succès.');

            return $this->redirectToRoute(
                'admin_reservation_details',
                ['id' => $reservation->getId()]
            );
        }

        return $this->render('admin/reservation_edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

}
