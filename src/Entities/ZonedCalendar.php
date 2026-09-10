<?php

declare(strict_types=1);

namespace Console\Entities;

use Spatie\IcalendarGenerator\Components\Calendar;

class ZonedCalendar extends Calendar
{
    public function toString(): string
    {
        // Spatie 2.6 can append Z to transition wall times when PHP defaults to UTC.
        // RFC 5545 section 3.8.2.4 requires local DTSTART in STANDARD/DAYLIGHT.
        // Limit the workaround to timezone components; event instants stay intact.
        return preg_replace_callback(
            '/BEGIN:VTIMEZONE\r\n.*?END:VTIMEZONE/s',
            static fn (array $match): string => preg_replace(
                '/(?<=\r\n)(DTSTART:\d{8}T\d{6})Z(?=\r\n)/',
                '$1',
                $match[0]
            ),
            parent::toString()
        );
    }
}
