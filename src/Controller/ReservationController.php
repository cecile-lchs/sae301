<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Client;
use App\Entity\Reservation;
use App\Entity\RendezVous;
use App\Form\ClientType;
use App\Repository\ReservationRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpClient\HttpClient;

final class ReservationController extends AbstractController
{
//    #[Route('/reservation/etape-1', name: 'reservation_step1')]
//    public function step1(Request $request, ServiceRepository $serviceRepo, SessionInterface $session): Response
//    {
//        if ($request->isMethod('POST')) {
//            $session->set('services', $request->request->get('services', []));
//            return $this->redirectToRoute('reservation_step2');
//        }
//
//        return $this->render('reservation/step1.html.twig', [
//            'services' => $serviceRepo->findAll()
//        ]);
//    }
    #[Route('/reservation/etape-1', name: 'reservation_step1')]
    public function step1(Request $request, ServiceRepository $serviceRepo, SessionInterface $session): Response
    {
        if ($request->isMethod('POST')) {
            $services = $request->request->all('services');
            $session->set('services', $services); // stocker dans session PHP
            return $this->redirectToRoute('reservation_step2');
        }

        return $this->render('reservation/step1.html.twig', [
            'services' => $serviceRepo->findAll(),
        ]);
    }

    #[Route('/reservation/etape-2', name: 'reservation_step2')]
    public function step2(Request $request, SessionInterface $session, ServiceRepository $serviceRepo): Response
    {
        if ($request->isMethod('POST')) {
            $session->set('date', $request->request->get('date'));
            $session->set('heure', $request->request->get('heure'));
            return $this->redirectToRoute('reservation_step3');
        }

        // On récupère les services sélectionnés depuis la session
        $serviceIds = $session->get('services', []); // retourne un tableau ou []
        $selectedServices = [];

        if (!empty($serviceIds)) {
            $selectedServices = $serviceRepo->findBy(['id' => $serviceIds]);
        }

        $totalPrice = 0;
        foreach ($selectedServices as $service) {
            $totalPrice += $service->getPrix();
        }
        return $this->render('reservation/step2.html.twig', [
            'selectedServices' => $selectedServices,
            'totalPrice' => $totalPrice
        ]);
    }


//    #[Route('/reservation/etape-3', name: 'reservation_step3')]
//    public function step3(Request $request, SessionInterface $session): Response
//    {
//        $client = new Client();
//        $form = $this->createForm(ClientType::class, $client);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $session->set('client', $client);
//            return $this->redirectToRoute('reservation_step4');
//        }
//
//        return $this->render('reservation/step3.html.twig', [
//            'form' => $form->createView()
//        ]);
//    }

//// src/Controller/ReservationController.php
//    #[Route('/reservation/etape-3', name: 'reservation_step3')]
//    public function step3(SessionInterface $session, ServiceRepository $serviceRepo): Response
//    {
//        // Récupérer les services
//        $serviceIds = $session->get('services', []);
//        $selectedServices = !empty($serviceIds) ? $serviceRepo->findBy(['id' => $serviceIds]) : [];
//
//        // Récupérer date et heure depuis step2
//        $reservationDateStr = $session->get('date');   // <--- était 'reservation_date' -> changer
//        $reservationTime = $session->get('heure');     // <--- était 'reservation_time' -> changer
//
//        $reservationDate = null;
//        if ($reservationDateStr) {
//            $reservationDate = new \DateTime($reservationDateStr);
//        }
//
//        // Infos du client
//        $reservationInfo = $session->get('reservation_info', []);
//
//        // Total
//        $totalPrice = array_sum(array_map(fn($s) => $s->getPrix(), $selectedServices));
//
//        return $this->render('reservation/step3.html.twig', [
//            'selectedServices' => $selectedServices,
//            'reservationDate' => $reservationDate,
//            'reservationTime' => $reservationTime,
//            'reservationInfo' => $reservationInfo,
//            'totalPrice' => $totalPrice,
//        ]);
//
//    }

    #[Route('/reservation/etape-3', name: 'reservation_step3', methods: ['GET','POST'])]
    public function step3(Request $request, SessionInterface $session, ServiceRepository $serviceRepo): Response
    {
        $serviceIds = $session->get('services', []);
        $selectedServices = !empty($serviceIds) ? $serviceRepo->findBy(['id' => $serviceIds]) : [];

        $reservationDateStr = $session->get('date');
        $reservationTime = $session->get('heure');
        $reservationDate = $reservationDateStr ? new \DateTime($reservationDateStr) : null;

        $reservationInfo = $session->get('reservation_info', []);

        if ($request->isMethod('POST')) {
            $reservationInfo = $request->request->all();
            $session->set('reservation_info', $reservationInfo);
            return $this->redirectToRoute('reservation_step4');
        }

        $totalPrice = array_sum(array_map(fn($s) => $s->getPrix(), $selectedServices));

        return $this->render('reservation/step3.html.twig', [
            'selectedServices' => $selectedServices,
            'reservationDate' => $reservationDate,
            'reservationTime' => $reservationTime,
            'reservationInfo' => $reservationInfo,
            'totalPrice' => $totalPrice,
        ]);
    }


    #[Route('/reservation/etape-4', name: 'reservation_step4')]
    public function step4(SessionInterface $session, EntityManagerInterface $em, ServiceRepository $serviceRepo): Response
    {

        // Récupérer infos du client depuis step3
        $reservationInfo = $session->get('reservation_info', []);

        if (empty($reservationInfo)) {
            $this->addFlash('error', 'Les informations client sont manquantes.');
            return $this->redirectToRoute('reservation_step3');
        }

        $client = new Client();
        $client->setNom($reservationInfo['nom'] ?? '');
        $client->setPrenom($reservationInfo['prenom'] ?? '');
        $client->setEmail($reservationInfo['email'] ?? '');
        $client->setTelephone($reservationInfo['phone'] ?? '');
        $client->setAdresse($reservationInfo['adresse'] ?? '');
        // latitude et longitude peuvent rester null pour l'instant

        // Récupérer services sélectionnés
        $servicesIds = $session->get('services', []);

        // Créer le rendez-vous
        $date = $session->get('date');
        $heure = $session->get('heure');

        $rendezVous = new RendezVous();
        $rendezVous->setDate(new \DateTime($date));
        $rendezVous->setHeureDebut(new \DateTime($heure));
        $rendezVous->setHeureFin((clone new \DateTime($heure))->modify('+1 hour'));

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setClient($client);
        $reservation->setRendezVous($rendezVous);
        $reservation->setStatus('en attente');
        $reservation->setDateCreation(new \DateTime());

        foreach ($servicesIds as $id) {
            $service = $serviceRepo->find($id);
            if ($service) {
                $reservation->addService($service);
            }
        }
        $clientHttp = HttpClient::create();
        $response = $clientHttp->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
            'query' => [
                'q' => $client->getAdresse(),
                'limit' => 1
            ]
        ]);
        $data = $response->toArray();

        if (!empty($data['features'])) {
            $coords = $data['features'][0]['geometry']['coordinates']; // [lon, lat]
            $client->setLongitude($coords[0]);
            $client->setLatitude($coords[1]);
        } else {
            // Option 1 : définir une valeur par défaut ou
            // Option 2 : retourner une erreur à l’utilisateur
            $client->setLongitude(0);
            $client->setLatitude(0);
        }
        $em->persist($client);
        $em->persist($rendezVous);
        $em->persist($reservation);
        $em->flush();

        $session->clear();

        return $this->render('reservation/recap.html.twig');
    }


    #[Route('/reservation/calendar-events', name: 'reservation_calendar_events')]
    public function calendarEvents(ReservationRepository $repo): JsonResponse
    {
        $events = [];

        foreach ($repo->findAll() as $reservation) {
            $events[] = [
                'title' => 'Réservé',
                'start' => $reservation->getRendezVous()->getDate()->format('Y-m-d'),
                'display' => 'background',
                'backgroundColor' => '#e5f3eb'
            ];
        }

        return $this->json($events);
    }

    #[Route('/reservation/availability', name: 'reservation_availability')]
    public function availability(Request $request, ReservationRepository $repo): JsonResponse
    {
        $dateStr = $request->query->get('date');
        if (!$dateStr) {
            return $this->json(['error' => 'Date manquante'], 400);
        }

        $date = new \DateTime($dateStr);
        $reservations = $repo->findByDate($date);

        $busySlots = [];
        foreach ($reservations as $res) {
            $start = $res->getRendezVous()->getHeureDebut();
            $end = (clone $start)->modify('+1 hour');
            $busySlots[] = $start->format('H:i') . '-' . $end->format('H:i');
        }

        $opening = ['start' => '08:00', 'end' => '18:00'];
        $lunch = '12:00-14:00';

        return $this->json([
            'opening' => $opening,
            'lunch' => $lunch,
            'busy' => $busySlots
        ]);
    }
}
