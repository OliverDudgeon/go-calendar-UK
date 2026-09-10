<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Console\Entities\CalendarManifest;
use Console\Entities\LeekDuckEvent;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function fixture(string $start, string $end): LeekDuckEvent
{
    return LeekDuckEvent::create([
        'eventID' => 'timezone-' . $start,
        'name' => 'Raid Hour',
        'eventType' => 'raid-hour',
        'heading' => 'Raid Hour',
        'link' => 'https://example.com/raid-hour',
        'image' => 'https://example.com/raid-hour.png',
        'start' => $start,
        'end' => $end,
        'extraData' => null,
    ]);
}

foreach (['2026-07-08', '2026-12-09', '2026-03-28', '2026-03-29', '2026-10-24', '2026-10-25'] as $date) {
    $event = fixture($date . 'T18:00:00', $date . 'T19:00:00');
    $calendar = CalendarManifest::create($event->type, 'Europe/London')->calendar;
    $calendar->event($event->asCalendarEvent('Europe/London'));
    $ics = $calendar->get();
    $day = str_replace('-', '', $date);
    check(str_contains($ics, "DTSTART;TZID=Europe/London:{$day}T180000\r\n"), "$date: expected London 18:00");
    check(str_contains($ics, "DTEND;TZID=Europe/London:{$day}T190000\r\n"), "$date: expected London 19:00 end");
    check(str_contains($ics, 'BEGIN:VTIMEZONE'), "$date: missing VTIMEZONE");
    check(str_contains($ics, 'TZID:Europe/London'), "$date: missing timezone definition");
}

// The same calendar must carry both UK transition rules.
$summer = fixture('2026-07-08T18:00:00', '2026-07-08T19:00:00');
$winter = fixture('2026-12-09T18:00:00', '2026-12-09T19:00:00');
$calendar = CalendarManifest::create($summer->type, 'Europe/London')->calendar;
$calendar->event([$summer->asCalendarEvent('Europe/London'), $winter->asCalendarEvent('Europe/London')]);
$ics = $calendar->get();
check(str_contains($ics, "DTSTART:20260329T010000\r\nTZOFFSETFROM:+0000\r\nTZOFFSETTO:+0100"), 'Missing spring transition');
check(str_contains($ics, "DTSTART:20261025T020000\r\nTZOFFSETFROM:+0100\r\nTZOFFSETTO:+0000"), 'Missing autumn transition');
check((bool) preg_match('/DTSTAMP:\d{8}T\d{6}Z/', $ics), 'DTSTAMP must remain UTC');

// Omitting the option still produces the original floating local times.
$calendar = CalendarManifest::create($summer->type)->calendar;
$calendar->event($summer->asCalendarEvent());
$ics = $calendar->get();
check(str_contains($ics, "DTSTART:20260708T180000\r\n"), 'Default feed must remain floating');
check(! str_contains($ics, 'TZID'), 'Default feed must not contain timezones');
check(! str_contains($ics, 'BEGIN:VTIMEZONE'), 'Default feed must not contain VTIMEZONE');

// Explicit source zones represent global instants, not local wall times.
foreach (['Z', '+00:00', '-04:00'] as $offset) {
    $hour = $offset === '-04:00' ? '13' : '17';
    $event = fixture("2026-07-08T{$hour}:00:00{$offset}", "2026-07-08T{$hour}:30:00{$offset}");
    $calendar = CalendarManifest::create($event->type, 'Europe/London')->calendar;
    $calendar->event($event->asCalendarEvent('Europe/London'));
    $ics = $calendar->get();
    check(str_contains($ics, "DTSTART;TZID=Europe/London:20260708T180000\r\n"), 'Source instant shifted');
    check(str_contains($ics, 'Starts at 18:00'), 'Description must match displayed London time');
}

$event = fixture('2026-03-28T00:00:00', '2026-03-30T00:00:00');
$calendar = CalendarManifest::create($event->type, 'Europe/London')->calendar;
$calendar->event($event->asCalendarEvent('Europe/London'));
$ics = $calendar->get();
check(str_contains($ics, "DTSTART;VALUE=DATE:20260328\r\n"), 'All-day start must remain date-only');
check(str_contains($ics, "DTEND;VALUE=DATE:20260331\r\n"), 'All-day exclusive end changed');
check(! str_contains($ics, 'TZID'), 'All-day dates must not carry TZID');

$command = new Symfony\Component\Console\Tester\CommandTester(new Console\Commands\GenerateCalendar());
foreach (['BST', '+01:00', 'GMT+1', 'invalid', ''] as $timezone) {
    check($command->execute(['--timezone' => $timezone]) === 2, 'Reject non-IANA timezone before fetching data');
}

echo "Time-zone regression tests passed.\n";
