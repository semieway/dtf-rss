<?php

require_once 'vendor/autoload.php';

use Bhaktaraz\RSSGenerator\Channel;
use Bhaktaraz\RSSGenerator\Feed;
use Bhaktaraz\RSSGenerator\Item;

$conn = pg_connect(getenv('DATABASE_URL'));
$query = pg_query($conn, 'SELECT * FROM articles ORDER BY fetched_at DESC');
$articles = pg_fetch_all($query, PGSQL_ASSOC);

// Create feed.
$feed = new Feed();

$channel = new Channel();
$channel->title('DTF: Пользовательские записи')
    ->description('Популярные пользовательские записи с DTF.ru')
    ->url('https://dtf.ru')
    ->pubDate(time())
    ->appendTo($feed);

foreach ($articles as $article) {
    $item = new Item();
    $item->title($article['title'])
        ->description($article['description'] ?? sprintf('<a href="%s">%s</a>', $article['url'], 'Link'))
        ->url($article['url'])
        ->creator($article['author'])
        ->enclosure($article['enclosure'] ?? '')
        ->pubDate($article['date'])
        ->appendTo($channel);
}

header('Content-Type: application/rss+xml; charset=utf-8');

echo $feed;
