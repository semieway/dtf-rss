<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

$apiToken = getenv('API_TOKEN');

// Fetch articles.
$client = new Client([
    'base_uri' => 'https://api.dtf.ru/v1.8/',
    'headers' => [
        'X-Device-Token' => $apiToken
    ]
]);

$response = $client->request('GET', 'timeline/index/popular');
$data = json_decode($response->getBody());
$articles = [];
$ids = [];

// Prepare articles info.
foreach ($data->result as $dataArticle) {
    $isLikesEnough = FALSE;
    if ($dataArticle->subsite->name == 'Мемы' || $dataArticle->subsite->name == 'Видео') {
        if ($dataArticle->likes->count >= 200) {
            $isLikesEnough = TRUE;
        }
    } elseif ($dataArticle->likes->count >= 100) {
        $isLikesEnough = TRUE;
    }

    if ($dataArticle->isEditorial == false && $isLikesEnough) {
        $ids[] = $dataArticle->id;
        $article = [];
        $article['dtf_id'] = $dataArticle->id;
        $article['url'] = $dataArticle->url;
        $article['date'] = $dataArticle->date;
        $article['author'] = $dataArticle->author->name;

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

// Get articles ids from database.
$conn = pg_connect(getenv('DATABASE_URL'));
$query = pg_query($conn, 'SELECT * FROM articles');
$dbIds = pg_fetch_all_columns($query, pg_field_num($query, 'dtf_id'));

// Filter fetched articles.
$diffIds = array_diff($ids, $dbIds);
$articles = array_filter($articles, function ($article) use ($diffIds) { return in_array($article['dtf_id'], $diffIds); });

//Write articles to database.
foreach ($articles as $article) {
    pg_insert($conn, 'articles', $article);
}
