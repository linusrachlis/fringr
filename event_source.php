<?php

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

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

$events = [];
$crawler->filter('.performances table tbody tr')->each(
    function (Crawler $node) use (&$events, $title, $runtime_minutes) {
        $cells = $node->filter('td');
        $date = $cells->eq(1)->text();
        $time = $cells->eq(2)->text();
        $start_time = new DateTime("$date, $time");
        $end_time = (new DateTime("$date, $time"))->add(new DateInterval("PT{$runtime_minutes}M"));
        $events[] = [
            'title' => $title,
            'start' => $start_time->format('c'),
            'end' => $end_time->format('c'),
        ];
    }
);

header('Content-type: application/json');
echo json_encode($events);
