<?php
header('Content-Type: application/json');

$apiKey = "2fc965128b004945be6fe8cbc6e8a745";
$topic = $_GET['topic'] ?? '';

if (empty($topic)) {
    echo json_encode(['error' => 'Nessun argomento specificato']);
    exit;
}

$url = "https://newsapi.org/v2/everything?q=" . urlencode($topic) . "&language=it&sortBy=publishedAt&apiKey=" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: NewsApp/1.0']);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$formattedNews = [];

if (isset($data['articles'])) {
    foreach ($data['articles'] as $article) {
        $formattedNews[] = [
            'title' => $article['title'],
            'description' => $article['description'],
            'source' => $article['source']['name'],
            'url' => $article['url'],
            'date' => date("d/m/Y H:i", strtotime($article['publishedAt']))
        ];
    }
    
    // Salviamo l'ultima ricerca nel file JSON locale come backup/log
    file_put_contents('last_search.json', json_encode($formattedNews, JSON_PRETTY_PRINT));
    
    echo json_encode($formattedNews);
} else {
    echo json_encode(['error' => 'Impossibile recuperare le notizie']);
}