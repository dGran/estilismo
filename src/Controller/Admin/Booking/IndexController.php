<?php

declare(strict_types=1);

namespace App\Controller\Admin\Booking;

use App\Manager\BookingManager;
use App\Services\AgendaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    private AgendaService $agendaService;

    private BookingManager $bookingManager;

    public function __construct(AgendaService $agendaService, BookingManager $bookingManager)
    {
        $this->agendaService = $agendaService;
        $this->bookingManager = $bookingManager;
    }

    #[Route('/admin/booking/{view}/{day?}', name: 'admin_booking', methods: 'GET')]
    public function __invoke(Request $request, string $view = null, \DateTime $day = null): Response
    {
        if ($view === null) {
            $view = 'day';
        }

        if ($day === null) {
            $day = new \DateTime();
        }

        $dateFrom = (clone $day)->setTime(0, 0);
        $dateTo = (clone $day)->setTime(23, 59, 59);
        $dayBookings = $this->bookingManager->findByDateFromAndDateTo($dateFrom, $dateTo);
        $slots = $this->agendaService->generateDaySlots($dateFrom, $dayBookings);

        $month = (int)$day->format('m');
        $year = (int)$day->format('Y');

        $calendarMonthData = $this->agendaService->getCalendarMonthData($month, $year);
        $daysOfTheWeek = AgendaService::DAYS_OF_THE_WEEK;
        $dayOfTheMonth = $day->format('d');

        return $this->render('admin/booking/index.html.twig', [
            'view' => $view,
            'day' => $day,
            'slots' => $slots,
            'calendar_month_data' => $calendarMonthData,
            'days_of_the_week' => $daysOfTheWeek,
            'day_of_the_month' => $dayOfTheMonth,
            'bookings' => $dayBookings,
        ]);
    }
}
