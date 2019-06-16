# Fringr core features

* paste in play urls to overlay all performances on calendar.
* provides ical feed URLs to subscribe to showtimes for plays in your own calendar.

## Implementation

* index.html
    - runs FullCalendar instance and adds one event source per play
* event_source.php
    - receives play URL, scrapes & returns event data in FullCalendar format (array of {title, start, end} objects)

# TODO

* click a performance to make it the selected one for the play. the others go dim.
    - export selected performances to .ics file
    - encode this into the value of the textarea so you can restore the state via copy/paste
* size calendar widget to window better
* provide month view option
* know if a show is sold out (sub-request to 'tickets' button, will take longer)
* show a map in a popup from clicking showtime in calendar
* time filter
