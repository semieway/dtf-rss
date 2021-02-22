<?php

//Remove all articles older than one week.
$conn = pg_connect(getenv('DATABASE_URL'));
$query = pg_query($conn, "SELECT * FROM articles WHERE fetched_at < (NOW() - INTERVAL '3 days')");
$articles = pg_fetch_all($query, PGSQL_ASSOC);

foreach ($articles as $article) {
    pg_delete($conn, 'articles', ['dtf_id' => $article['dtf_id']]);
}
