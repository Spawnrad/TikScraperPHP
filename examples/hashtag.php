<?php
require __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json');
$api = new \TikScraper\Api([
    'signer' => [
        'method' => 'remote',
        'url' => 'http://localhost:8080/signature'
    ]
]);
$item = $api->hashtag('funny');
$full = $item->feed()->getFull();
echo $full->toJson();
