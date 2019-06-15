<?php

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Eluceo\iCal;

require 'vendor/autoload.php';
date_default_timezone_set('America/Toronto');
$url_prefix = 'https://fringetoronto.com/fringe/show/';

if (
    !isset($_GET['play_url']) ||
    substr($_GET['play_url'], 0, strlen($url_prefix)) != $url_prefix
)
{
    header('HTTP/1.1 400 Bad Request');
    header('Content-type: application/json');
    echo json_encode([
        'message' =>
            "play_url param is required and must begin with " . var_export($url_prefix, true)
    ]);
    exit;
}

$client = new Client();
$crawler = $client->request('GET', $_GET['play_url']);

$title = trim($crawler->filter('.page-title')->text());
$runtime_text = $crawler->filter('.show-info')->first()->filter('.column.right dd')->text();
$runtime_minutes = preg_replace('/^(\d+)m$/', '$1', $runtime_text);

$location_address_node = $crawler->filter('address.venue-address');

$location_name = $location_address_node->previousAll()->text();
$location_name = preg_replace('@^\s*\d+\s*:\s*(.+)$@', '$1', $location_name);

$location_address_html = $location_address_node->filter('p:first-child')->html();
$location_address = preg_replace('@<br( /)?>@', ', ', $location_address_html);
$location_address = strip_tags($location_address);

$events = [];
$timezone = new DateTimeZone('America/Toronto');

$crawler->filter('.performances table tbody tr')->each(
    function (Crawler $node) use (&$events, $title, $runtime_minutes, $location_name, $location_address) {
        $cells = $node->filter('td');
        $date = $cells->eq(1)->text();


        // Filter out the '*' the Fringe website uses '*' after performance times to
        // indicate an accessible performance.
        // $accessible = $cells->eq(3)->filter('.accessibility-flag')->count() > 0;
        // TODO update for 2019's more detailed a11y flags.
        // "level of physical access" is on venue, but other flags are show-level.


        $time = preg_replace('/^.*?(\d+:\d+[ap]m).*?$/', '$1', $cells->eq(2)->text());
        $start_time = new DateTime("$date, $time", $timezone);
        $end_time = (new DateTime("$date, $time", $timezone))->add(new DateInterval("PT{$runtime_minutes}M"));
        $events[] = [
            'title' => "$title @ $location_name, $location_address",
            'start' => $start_time->format('c'),
            'end' => $end_time->format('c'),
            'url' => $_GET['play_url'],
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

    $utc = new DateTimeZone('UTC');

    foreach ($events as $event)
    {
        $dtStart = new DateTime($event['start'], $timezone);
        $dtStart->setTimezone($utc);
        $dtEnd = new DateTime($event['end'], $timezone);
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
    }

    echo $vCalendar->render();
}
