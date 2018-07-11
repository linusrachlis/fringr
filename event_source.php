<?php

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Eluceo\iCal;

require 'vendor/autoload.php';
date_default_timezone_set('America/Toronto');

assert(isset($_GET['play_url']));
$url_prefix = 'https://fringetoronto.com/festivals/fringe/event/';
assert(substr($_GET['play_url'], 0, strlen($url_prefix)) == $url_prefix);

$client = new Client();
$crawler = $client->request('GET', $_GET['play_url']);

$title = trim($crawler->filter('.page-title')->text());
$runtime_text = $crawler->filter('.show-info')->first()->filter('.column.right dd')->text();
$runtime_minutes = preg_replace('/^(\d+)m$/', '$1', $runtime_text);
$location_name = $crawler->filter('.show-info > h3')->text();
$location_address_html = $crawler->filter('.show-info > address')->html();
$location_address = preg_replace('@<br( /)?>@', ', ', $location_address_html);

$events = [];
$crawler->filter('.performances table tbody tr')->each(
    function (Crawler $node) use (&$events, $title, $runtime_minutes) {
        $cells = $node->filter('td');
        $date = $cells->eq(1)->text();
        // Filter out the '*' the Fringe website uses '*' after performance times to
        // indicate an accessible performance.
        // TODO indicate accessibility on the calendar too!
        $time = preg_replace('/^.*?(\d+:\d+[ap]m).*?$/', '$1', $cells->eq(2)->text());
        $start_time = new DateTime("$date, $time");
        $end_time = (new DateTime("$date, $time"))->add(new DateInterval("PT{$runtime_minutes}M"));
        $events[] = [
            'title' => $title,
            'start' => $start_time->format('c'),
            'end' => $end_time->format('c'),
        ];
    }
);

if (!isset($_GET['format'])) $_GET['format'] = 'fullcalendar';

switch ($_GET['format'])
{
    case 'ical':
        output_ical($events);
        break;
    case 'fullcalendar':
    default:
        header('Content-type: application/json');
        echo json_encode($events);
        break;
}

// ----------------------------------

function output_ical(array $events)
{
    global $location_name;
    global $location_address;

    header('Content-type: text/calendar; charset=utf8');

    $vCalendar = new iCal\Component\Calendar('fringr.linus.rachlis.net');
    $vCalendar->setName($events[0]['title'] . ' - Fringe');
    $vCalendar->setDescription($events[0]['title'] . ' - Fringe');

    $torontoTz = new DateTimeZone('America/Toronto');
    $utc = new DateTimeZone('UTC');

    foreach ($events as $event)
    {
        $dtStart = new DateTime($event['start'], $torontoTz);
        $dtStart->setTimezone($utc);
        $dtEnd = new DateTime($event['end'], $torontoTz);
        $dtEnd->setTimezone($utc);

        $uid = sha1($event['title']) . '_' . strtotime($event['start']) . '@fringr.linus.rachlis.net';

        $vEvent = new iCal\Component\Event();
        $vEvent
            ->setSummary($event['title'])
            ->setDtStart($dtStart)
            ->setDtEnd($dtEnd)
            ->setLocation("$location_name, $location_address")
            ->setUniqueId($uid)
            ;

        $vCalendar->addComponent($vEvent);

        // echo "BEGIN:VEVENT\n";
        // $title_safe = str_replace(["\n", "\r"], ' ', $event['title']);
        // echo "SUMMARY:$title_safe\n";
        // $uid = sha1($event['title']) . '_' . strtotime($event['start']) . '@fringr.linus.rachlis.net';
        // echo "UID:$uid\n";
        // echo "DTSTART:$event[start]\n";
        // echo "DTEND:$event[end]\n";
        // $location_safe = str_replace(["\n", "\r"], ' ', "$location_name, $location_address");
        // echo "LOCATION:$location_safe\n";
        // echo "END:VEVENT\n";
        // echo "\n";
        // echo "\n";
        // echo "\n";
        // echo "\n";
        // echo "\n";
        // echo "\n";
        // echo "\n";
        // echo "\n";
    }

    // echo "END:VCALENDAR\n";

    echo $vCalendar->render();
}
