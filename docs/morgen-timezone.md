# Morgen time-zone investigation

Checked 10 September 2026 against the public latest-release feed at
<https://github.com/othyn/go-calendar/releases/latest/download/gocal.ics>.
The Zacian (Hero of Many Battles) Raid Hour has:

```ical
DTSTART:20260909T180000
DTEND:20260909T190000
```

These are floating times: no `TZID` and no `Z`. Under
[RFC 5545 section 3.3.5](https://www.rfc-editor.org/rfc/rfc5545.txt),
18:00 should remain 18:00 in the observer's local zone. The generator deliberately
strips event time zones and disables automatic calendar timezone components.

[Morgen's event API documentation](https://docs.morgen.so/events) states that
floating timed events are not reliably preserved when written to connected
calendars and recommends explicit IANA zones. That concerns API writes; it does
not prove how an ICS subscription is parsed. No Morgen client reproduction was
performed here. Treat UTC normalization as a hypothesis, not a confirmed cause.

The optional `gen --timezone=Europe/London` output provides a UK-specific
workaround: unzoned source timestamps are interpreted as London wall time and
Spatie emits `TZID` and `VTIMEZONE` information. Timestamps with explicit source
offsets retain their instant. The default remains the global floating feed.
The locked Spatie 2.6 serializer adds a UTC `Z` to transition wall times when
PHP defaults to UTC. `ZonedCalendar` removes that suffix only from `DTSTART`
inside `VTIMEZONE`, as required by RFC 5545 section 3.8.2.4. Event instants and
`DTSTAMP` remain untouched. The regression checks cover the actual spring and
autumn transition times and offsets.

All-day dates retain the generator's existing date-only and exclusive-end policy.

To verify in Morgen, host UK output at a separate subscription URL and compare
an 18:00 summer event and an 18:00 winter event with the floating feed, with
Morgen set to Europe/London. Both UK events should display at 18:00. Check after
subscription refresh; record Morgen version, connected provider, calendar zone,
and the exact event UID if a discrepancy remains.
