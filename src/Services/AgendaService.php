<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Booking;
use App\Manager\BookingManager;
use App\Manager\PublicHolidayManager;
use App\Model\Agenda\SlotBooking;

class AgendaService
{
    private PublicHolidayManager $publicHolidayManager;
    private BookingManager $bookingManager;

    public function __construct(PublicHolidayManager $publicHolidayManager, BookingManager $bookingManager) {
        $this->publicHolidayManager = $publicHolidayManager;
        $this->bookingManager = $bookingManager;
    }

    public const AVAILABLE_DAYS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
    ];

    public const DAYS_OF_THE_WEEK = [
        1 => [
            'name' => 'Monday',
            'short_name' => 'Lu',
        ],
        2 => [
            'name' => 'Tuesday',
            'short_name' => 'Ma',
        ],
        3 => [
            'name' => 'Wednesday',
            'short_name' => 'We',
        ],
        4 => [
            'name' => 'Thursday',
            'short_name' => 'Th',
        ],
        5 => [
            'name' => 'Friday',
            'short_name' => 'Fr',
        ],
        6 => [
            'name' => 'Saturday',
            'short_name' => 'Sa',
        ],
        7 => [
            'name' => 'Sunday',
            'short_name' => 'Su',
        ],
    ];

    public const WORKING_HOURS_START_TIME = '09:30';
    public const WORKING_HOURS_END_TIME = '19:00';
    public const ADITIONAL_START_TIME = '08:00';
    public const ADITIONAL_END_TIME = '22:00';

    //TODO: CREAR SLOTS EN FUNCION DE HORARIOS OFICIALES POR DIA, POR EJEMPLO, LOS VIERNES SE VA A CERRAR ANTES
    //TODO: ALMACENAR TAMBIEN PARA QUE PUEDA SER CONFIGURABLE LOS WORKING HOURS START Y END Y LOS ADITIONAL, y así no tener que usar las constantes
    //TODO: CREAR TABLA CON HORARIOS PARA QUE PUEDAN SER EDITABLES

    public const SLOT_INTERVAL = 15;

    public function getDayBookings(\DateTime $day): void
    {
        $dateTo = clone $day;
        $dateTo->modify('+1 day');
        $dayBookings = $this->bookingManager->findByDateFromAndDateTo($day, $dateTo);
    }

    /**
     * @param Booking[] $bookings
     *
     * @return array<string, Booking[]>
     */
    public function generateDaySlots(\DateTime $day, array $bookings): array
    {
        $slots = [];
        $numberDayOfTheWeek = $day->format('N');

//        if (!\array_key_exists($numberDayOfTheWeek, self::AVAILABLE_DAYS)) {
//            return $slots;
//        }

        $monthPublicHolidays = $this->getPublicHolidays((int)$day->format('m'), (int)$day->format('Y'));
        $isPublicHoliday = false;

        if (\array_key_exists((int)$day->format('d'), $monthPublicHolidays)) {
            $isPublicHoliday = true;
        }

        [$startHour, $startMinute] = \explode(':', self::ADITIONAL_START_TIME);
        [$endHour, $endMinute] = \explode(':', self::ADITIONAL_END_TIME);
        [$workingHoursStartHour, $workingHoursStartMinute] = \explode(':', self::WORKING_HOURS_START_TIME);
        [$workingHoursEndHour, $workingHoursEndMinute] = \explode(':', self::WORKING_HOURS_END_TIME);
        $startDate = (clone $day)->setTime((int)$startHour, (int)$startMinute);
        $endDate = (clone $day)->setTime((int)$endHour, (int)$endMinute);
        $workingHoursStartDate = (clone $day)->setTime((int)$workingHoursStartHour, (int)$workingHoursStartMinute);
        $workingHoursEndDate = (clone $day)->setTime((int)$workingHoursEndHour, (int)$workingHoursEndMinute);

        $currentSlotDate = clone $startDate;

        while ($currentSlotDate <= $endDate) {
            $currentSlotFinishDate = clone $currentSlotDate;
            $currentSlotFinishDate->modify('+'.self::SLOT_INTERVAL.' minutes');
            $currentSlotFinishDate = \min($currentSlotFinishDate, $endDate);
            $slotBookings = [];

            foreach ($bookings as $bookingKey => $booking) {
                $bookingDate = $booking->getDate();

                if ($bookingDate === null) {
                    continue;
                }

                $estimatedDuration = $booking->getEstimatedDuration() ?? self::SLOT_INTERVAL;

                $bookingStart = clone $bookingDate;
                $bookingEnd = clone $bookingDate;
                $bookingEnd->modify('+'.$estimatedDuration.' minutes');

                if (
                    $bookingStart <= $currentSlotDate && $bookingEnd >= $currentSlotFinishDate
                ) {
                    $slotBooking = new SlotBooking();
                    $slotBooking->booking = $booking;

                    try {
                        $slotBooking->color = SlotBooking::COLORS[$bookingKey];
                    } catch(\Throwable $exception) {
                        $slotBooking->color = SlotBooking::DEFAULT_COLOR;
                    }

                    $slotBookings[] = $slotBooking;
                }
            }

            $isWorkingHours = $currentSlotDate >= $workingHoursStartDate && $currentSlotDate <= $workingHoursEndDate;

            $slots[$currentSlotDate->format('Y-m-d H:i:s')] = [
                'slotBooking' => $slotBookings,
                'isWorkingHours' => $isWorkingHours && !$isPublicHoliday,
            ];

            $currentSlotDate->modify('+'.self::SLOT_INTERVAL.' minutes');
        }

        return $slots;
    }

    /**
     * @return array{year: int, month: int, mont_name: string, number_of_days: int, public_holidays: array, business_days: int}
     */
    public function getCalendarMonthData(int $month, int $year): array
    {
        $firstDay = new \DateTime("$year-$month-01");
        $numberOfDays = $firstDay->format('t');
        $monthName = $firstDay->format('F');

        $publicHolidays = $this->getPublicHolidays($month, $year);

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'number_of_days' => $numberOfDays,
            'public_holidays' => $publicHolidays,
            'business_days' => $numberOfDays - \count($publicHolidays),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getPublicHolidays(int $month, int $year): array
    {
        $weekendDaysOfMonth = $this->getWeekendDaysOfMonth($month, $year);
        $publicHolidays = $this->publicHolidayManager->findByMonthAndYear($month, $year);

        $publicHolidaysAndWeekendDays = [];

        foreach ($publicHolidays as $publicHoliday) {
            $publicHolidaysAndWeekendDays[(int)$publicHoliday->getDate()->format('d')] = $publicHoliday->getName();
        }

        foreach ($weekendDaysOfMonth as $weekendDayOfMonth) {
            if (!\array_key_exists($weekendDayOfMonth, $publicHolidaysAndWeekendDays)) {
                $publicHolidaysAndWeekendDays[$weekendDayOfMonth] = 'Fin de semana';
            }
        }

        return $publicHolidaysAndWeekendDays;
    }

    /**
     * @return array<int, int>
     */
    private function getWeekendDaysOfMonth(int $month, int $year): array
    {
        $weekendDays = [];
        $firstDayOfMonth = new \DateTime("$year-$month-01");
        $lastDayOfMonth = (new \DateTime("$year-$month-01"))->modify('last day of this month');

        while ($firstDayOfMonth <= $lastDayOfMonth) {
            if ($firstDayOfMonth->format('N') >= 6) {
                $weekendDays[] = (int)$firstDayOfMonth->format('j');
            }
            $firstDayOfMonth->modify('+1 day');
        }

        return $weekendDays;
    }
}
