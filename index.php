<?php

require_once 'vendor/autoload.php';

use Bhaktaraz\RSSGenerator\Channel;
use Bhaktaraz\RSSGenerator\Feed;
use Bhaktaraz\RSSGenerator\Item;
use GuzzleHttp\Client;

$apiToken = getenv('API_TOKEN');

// Fetch articles.
$client = new Client([
    'base_uri' => 'https://api.dtf.ru/v1.8/',
    'headers' => [
        'X-Device-Token' => $apiToken
    ]
]);

$response = $client->request('GET', 'timeline/index/day');
$data = json_decode($response->getBody());
$articles = [];

// Prepare articles info.
foreach ($data->result as $dataArticle) {
    if ($dataArticle->isEditorial == false && $dataArticle->likes->count >= 100) {
        $article = [];
        $article['url'] = $dataArticle->url;

        if (!empty($dataArticle->title)) {
            $article['title'] = $dataArticle->title;
        } else {
            $article['title'] = 'Запись в подсайте '.$dataArticle->subsite->name;
        }

        if (!empty($dataArticle->intro)) {
            $article['description'] = $dataArticle->intro;
        }

        if (!empty($dataArticle->cover)) {
            $article['enclosure'] = $dataArticle->cover->thumbnailUrl;
        }

        $articles[] = $article;
    }
}

// Create feed.
$feed = new Feed();

$channel = new Channel();
$channel->title('DTF: Топ пользовательских записей')
    ->description('Пользовательские записи с DTF.ru с рейтингом > 100')
    ->url('https://dtf.ru/all/top/day')
    ->appendTo($feed);

foreach ($articles as $article) {
    $item = new Item();
    $item->title($article['title'])
        ->description($article['description'] ?? '')
        ->url($article['url'])
        ->enclosure($article['enclosure'] ?? '')
        ->appendTo($channel);
}

echo $feed;
