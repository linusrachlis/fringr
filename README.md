# Fringr core features

* paste in play urls to overlay all performances.
* TODO: click a performance to make it the selected one for the play. the others go dim.
    - encode this into the value of the textarea so you can restore the state via copy/paste

## Implementation

* index.html
    - runs FullCalendar instance and adds one event source per play
* event_source.php
    - receives play URL, scrapes & returns event data in FullCalendar format (array of {title, start, end} objects)

# Would be nice

* size calendar widget to window better
* provide month view option
* know if a show is sold out (sub-request to 'tickets' button, will take longer)
* show the name of the theatre / address
* show a map in a popup
* provide persistent storage and gcal-compatible feed URL
* maintain link to URL in case info changes, calendar can update
* notify if chosen time disappears?
* know if a show is sold out (sub-request)
