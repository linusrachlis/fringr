# Fringr core features

* paste in play urls to overlay all performances on calendar.
* provides ical feed URLs to subscribe to showtimes for plays in your own calendar.

## Implementation

* index.html
    - runs FullCalendar instance and adds one event source per play
* event_source.php
    - receives play URL, scrapes & returns event data in FullCalendar format (array of {title, start, end} objects)

# TODO

* don't use assertions for input checking! 😠
* click a performance to make it the selected one for the play. the others go dim.
    - export selected performances to .ics file
    - encode this into the value of the textarea so you can restore the state via copy/paste
* error handling for bad URLs
* size calendar widget to window better
* provide month view option
* know if a show is sold out (sub-request to 'tickets' button, will take longer)
* show the name of the theatre / address in calendar item
* show a map in a popup from clicking showtime in calendar
* maintain link to URL in case info changes, calendar can update
* notify if chosen time disappears?
* update scraper for 2019 when the website may use different markup
