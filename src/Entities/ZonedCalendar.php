<?php

declare(strict_types=1);

namespace Console\Entities;

use Console\Services\CalendarService;
use Spatie\IcalendarGenerator\Components\Calendar;

class ZonedCalendar extends Calendar
{
    public function toString(): string
    {
        // Spatie 2.6 constructs transition wall times in PHP's default zone.
        // Use a non-UTC, DST-free zone to avoid both a Z suffix and DST arithmetic.
        // This affects only serialization, not the zones attached to event dates.
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set(CalendarService::TIMEZONE);

        try {
            return parent::toString();
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }
}
