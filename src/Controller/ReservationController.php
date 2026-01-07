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
    public function step2(Request $request, SessionInterface $session, ServiceRepository $serviceRepo, \App\Repository\IndisponibiliteRepository $indispoRepo, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $date = $request->request->get('date');
            $heure = $request->request->get('heure');

            if (!$date || !$heure) {
                $this->addFlash('error', 'Veuillez sélectionner une date et un horaire valide.');
                // Redirect to preserve state or stay on page
                // Ideally we re-render, but redirect is safer to avoid state issues, flash will show up
                // But wait, if we redirect, we need to ensure inputs are re-populated?
                // Since it's JS driven, JS handles state if session is updated? No.
                // Let's just return to the details.
                // Note: Step 2 relies on JS for rendering, flash message is enough.
            } else {
                $session->set('date', $date);
                $session->set('heure', $heure);
                return $this->redirectToRoute('reservation_step3');
            }
        }

        // On récupère les services sélectionnés depuis la session
        $serviceIds = $session->get('services', []); // retourne un tableau ou []
        $selectedServices = [];

        if (!empty($serviceIds)) {
            $selectedServices = $serviceRepo->findBy(['id' => $serviceIds]);
        }

        $totalPrice = 0;
        $totalDuration = 0;
        foreach ($selectedServices as $service) {
            $totalPrice += $service->getPrix();
            $totalDuration += $service->getDureeMinutes();
        }

        // Unavailabilities Logic
        // 1. Admin defined unavailabilities
        $allIndisponibilites = $indispoRepo->findAll();
        $unavailabilities = [];

        foreach ($allIndisponibilites as $indispo) {
            $start = $indispo->getDebut();
            $end = $indispo->getFin();
            $motif = $indispo->getType();

            if ($start && $end) {
                $current = \DateTime::createFromInterface($start);
                $endDt = \DateTime::createFromInterface($end);

                while ($current <= $endDt) {
                    // Key by date for easy lookup
                    $unavailabilities[$current->format('Y-m-d')] = [
                        'date' => $current->format('Y-m-d'),
                        'motif' => $motif,
                    ];
                    $current->modify('+1 day');
                }
            }
        }

        // 2. Capacity Check (Check if day has enough space for totalDuration)
        // We check next 3 months mainly
        $startDate = new \DateTime();
        $endDate = (clone $startDate)->modify('+3 months');

        // Fetch all reservations
        $repo = $entityManager->getRepository(Reservation::class);
        $reservations = $repo->findAll();

        // Organize reservations by date
        $resByDate = [];
        foreach ($reservations as $r) {
            if ($r->getRendezVous()) {
                $d = $r->getRendezVous()->getDate()->format('Y-m-d');
                $resByDate[$d][] = $r;
            }
        }

        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dStr = $currentDate->format('Y-m-d');

            // Skip if already unavailable
            if (!isset($unavailabilities[$dStr])) {
                // Check capacity
                $dayRes = $resByDate[$dStr] ?? [];
                if (!$this->checkDayCapacity($dayRes, $totalDuration)) {
                    $unavailabilities[$dStr] = [
                        'date' => $dStr,
                        'motif' => 'Complet (Durée insuffisante)',
                    ];
                }
            }
            $currentDate->modify('+1 day');
        }

        return $this->render('reservation/step2.html.twig', [
            'selectedServices' => $selectedServices,
            'totalPrice' => $totalPrice,
            'totalDuration' => $totalDuration,
            // Reset keys to be a simple array for JSON serialization
            'unavailabilities' => array_values($unavailabilities)
        ]);
    }

    private function checkDayCapacity(array $reservations, int $durationMinutes): bool
    {
        // Opening: 08:00 - 18:00
        // Lunch: 12:00 - 14:00 (Unavailable)

        // Build busy slots
        $busy = [];
        // Lunch
        $busy[] = ['start' => 12 * 60, 'end' => 14 * 60];

        foreach ($reservations as $r) {
            $rdv = $r->getRendezVous();
            if ($rdv) {
                $s = $rdv->getHeureDebut();
                $e = $rdv->getHeureFin();
                $startMin = intval($s->format('H')) * 60 + intval($s->format('i'));
                $endMin = intval($e->format('H')) * 60 + intval($e->format('i'));
                $busy[] = ['start' => $startMin, 'end' => $endMin];
            }
        }

        // Sort busy slots
        usort($busy, fn($a, $b) => $a['start'] <=> $b['start']);

        // Check gaps
        $dayStart = 8 * 60;
        $dayEnd = 18 * 60;

        $currentPointer = $dayStart;

        foreach ($busy as $slot) {
            // Check gap before this slot
            if ($slot['start'] > $currentPointer) {
                $gap = $slot['start'] - $currentPointer;
                if ($gap >= $durationMinutes)
                    return true;
            }
            // Move pointer
            if ($slot['end'] > $currentPointer) {
                $currentPointer = $slot['end'];
            }
        }

        // Check final gap
        if ($dayEnd > $currentPointer) {
            $gap = $dayEnd - $currentPointer;
            if ($gap >= $durationMinutes)
                return true;
        }

        return false;
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

    #[Route('/reservation/etape-3', name: 'reservation_step3', methods: ['GET', 'POST'])]
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
            $errors = [];

            // Validation individual fields
            if (empty($reservationInfo['nom']))
                $errors['nom'] = 'Le nom est obligatoire';
            if (empty($reservationInfo['prenom']))
                $errors['prenom'] = 'Le prénom est obligatoire';

            // Email validation
            if (empty($reservationInfo['email'])) {
                $errors['email'] = "L'adresse e-mail est obligatoire";
            } elseif (!filter_var($reservationInfo['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Le format de l'adresse e-mail est invalide";
            }

            // Phone validation (digits only)
            if (empty($reservationInfo['phone'])) {
                $errors['phone'] = 'Le numéro de téléphone est obligatoire';
            } elseif (!preg_match('/^[0-9]+$/', $reservationInfo['phone'])) {
                $errors['phone'] = 'Le numéro de téléphone doit contenir uniquement des chiffres';
            }

            if (empty($reservationInfo['adresse']))
                $errors['adresse'] = "L'adresse est obligatoire";

            $session->set('reservation_info', $reservationInfo);

            if (empty($errors)) {
                return $this->redirectToRoute('reservation_step4');
            }
            // If errors, we fall through to render the page with errors
            // No flash message needed as we show specific errors
        }

        $totalPrice = 0;
        $totalDuration = 0;
        foreach ($selectedServices as $service) {
            $totalPrice += $service->getPrix();
            $totalDuration += $service->getDureeMinutes();
        }

        $reservationEndTime = null;
        if ($reservationTime && $reservationDate) {
            // Calculate end time
            $startParts = explode(':', $reservationTime);
            $startMinutes = intval($startParts[0]) * 60 + intval($startParts[1]);
            $endMinutes = $startMinutes + $totalDuration;

            $h = floor($endMinutes / 60);
            $m = $endMinutes % 60;
            $reservationEndTime = sprintf("%02d:%02d", $h, $m);
        }

        return $this->render('reservation/step3.html.twig', [
            'selectedServices' => $selectedServices,
            'reservationDate' => $reservationDate,
            'reservationTime' => $reservationTime,
            'reservationEndTime' => $reservationEndTime,
            'reservationInfo' => $reservationInfo,
            'totalPrice' => $totalPrice,
            'errors' => $errors ?? []
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

        $serviceIds = $session->get('services', []);
        $selectedServices = !empty($serviceIds) ? $serviceRepo->findBy(['id' => $serviceIds]) : [];

        $dateStr = $session->get('date');
        $heureStr = $session->get('heure');

        $reservationDate = $dateStr ? new \DateTime($dateStr) : null;
        $reservationTime = $heureStr ? new \DateTime($heureStr) : null;
        $reservationEndTime = null;

        $totalPrice = 0;
        $totalDuration = 0;
        foreach ($selectedServices as $service) {
            $totalPrice += $service->getPrix();
            $totalDuration += $service->getDureeMinutes();
        }

        if ($reservationTime) {
            $reservationEndTime = (clone $reservationTime)->modify("+{$totalDuration} minutes");
        }

        return $this->render('reservation/recap.html.twig', [
            'clientInfo' => $reservationInfo,
            'selectedServices' => $selectedServices,
            'reservationDate' => $reservationDate,
            'reservationTime' => $reservationTime,
            'reservationEndTime' => $reservationEndTime,
            'totalPrice' => $totalPrice,
            'success' => false
        ]);
    }

    #[Route('/reservation/validation', name: 'reservation_validation')]
    public function validate(SessionInterface $session, EntityManagerInterface $em, ServiceRepository $serviceRepo): Response
    {
        $reservationInfo = $session->get('reservation_info', []);
        if (empty($reservationInfo)) {
            return $this->redirectToRoute('reservation_step1');
        }

        $client = new Client();
        $client->setNom($reservationInfo['nom'] ?? '');
        $client->setPrenom($reservationInfo['prenom'] ?? '');
        $client->setEmail($reservationInfo['email'] ?? '');
        $client->setTelephone($reservationInfo['phone'] ?? '');
        $client->setAdresse($reservationInfo['adresse'] ?? '');

        // Get Services
        $servicesIds = $session->get('services', []);
        $selectedServices = !empty($servicesIds) ? $serviceRepo->findBy(['id' => $servicesIds]) : [];

        // Create RDV
        $date = $session->get('date');
        $heure = $session->get('heure');

        $rendezVous = new RendezVous();
        $rendezVous->setDate(new \DateTime($date));
        $rendezVous->setHeureDebut(new \DateTime($heure));

        // Calculate total duration for End Time
        $totalDuration = 0;
        $totalPrice = 0;
        foreach ($selectedServices as $s) {
            $totalDuration += $s->getDureeMinutes();
            $totalPrice += $s->getPrix();
        }
        $rendezVous->setHeureFin((clone new \DateTime($heure))->modify("+{$totalDuration} minutes"));

        // Reservation
        $reservation = new Reservation();
        $reservation->setClient($client);
        $reservation->setRendezVous($rendezVous);
        $reservation->setStatus('en attente');
        $reservation->setDateCreation(new \DateTime());

        foreach ($selectedServices as $service) {
            $reservation->addService($service);
        }

        // Geocoding
        $clientHttp = HttpClient::create();
        try {
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
                $client->setLongitude(0);
                $client->setLatitude(0);
            }
        } catch (\Exception $e) {
            $client->setLongitude(0);
            $client->setLatitude(0);
        }

        $em->persist($client);
        $em->persist($rendezVous);
        $em->persist($reservation);
        $em->flush();

        $session->clear();

        return $this->render('reservation/success.html.twig', [
            'clientInfo' => $reservationInfo,
            'selectedServices' => $selectedServices,
            'reservationDate' => $rendezVous->getDate(),
            'reservationTime' => $rendezVous->getHeureDebut(),
            'reservationEndTime' => $rendezVous->getHeureFin(),
            'totalPrice' => $totalPrice
        ]);
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
