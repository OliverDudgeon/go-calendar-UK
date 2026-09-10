<?php

declare(strict_types=1);

namespace Console\Entities;

use Console\Services\CalendarService;
use Spatie\IcalendarGenerator\Components\Calendar;

class CalendarManifest
{
    public function __construct(
        public LeekDuckEventType $eventType,
        public Calendar $calendar,
    ) {
    }

    /**
     * Create a new CalendarManifest object.
     */
    public static function create(LeekDuckEventType $eventType, ?string $timezone = null): self
    {
        $manifest = new self(
            eventType: $eventType,
            calendar: ($timezone === null ? Calendar::create() : new ZonedCalendar())
                ->name(
                    name: 'GO Calendar - ' . $eventType->title . ($eventType->title == CalendarService::EVERYTHING_CALENDAR_NAME ? '' : ' (' . acronymForEventType($eventType) . ')') . ($timezone === null ? '' : " [{$timezone}]")
                )
                ->description(
                    description: 'All Pokémon GO ' . ($eventType->title == CalendarService::EVERYTHING_CALENDAR_NAME ? '' : "{$eventType->title} ") . 'events, in ' . ($timezone ?? 'your local time') . ', auto-updated and sourced from Leek Duck.'
                )
                ->refreshInterval(
                    minutes: 1440 // 1 day
                )
        );

        if ($timezone === null) {
            $manifest->calendar->withoutAutoTimezoneComponents()->withoutTimezone();
        }

        return $manifest;
    }
}
