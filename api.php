<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permette al frontend di interrogare l'API

$category = $_GET['category'] ?? '';

$feeds = [
    "politica" => [
        "Il Fatto Quotidiano" => "https://www.ilfattoquotidiano.it/politica/feed/",
        "ANSA"                => "https://www.ansa.it/sito/notizie/politica/politica_rss.xml",
        "Il Manifesto"        => "https://ilmanifesto.it/feed/",
        "Libero Quotidiano"   => "https://www.liberoquotidiano.it/rss/politica.xml",
        "La Repubblica"       => "https://www.repubblica.it/rss/politica/rss2.0.xml",
        "Corriere della Sera" => "https://xml2.corriereobjects.it/rss/politica.xml"
    ],
    "sport" => [
        "Sky Sport"           => "https://sport.sky.it/rss/sport_all.xml",
        "Gazzetta dello Sport"=> "https://www.gazzetta.it/rss/home.xml",
        "Corriere dello Sport"=> "https://www.corrieredellosport.it/rss/calcio",
        "Rai News Sport"      => "https://www.rainews.it/rss/sport",
        "Eurosport"           => "https://it.eurosport.com/rss.xml"
    ]
];

if (!isset($feeds[$category])) {
    echo json_encode(["error" => "Categoria non valida"]);
    exit;
}

$all_articles = [];

foreach ($feeds[$category] as $source => $url) {
    // Gestione errori: timeout di 3 secondi per evitare blocchi
    $context = stream_context_create(['http' => ['timeout' => 3]]);
    $xmlData = @file_get_contents($url, false, $context);

    if ($xmlData === false) {
        continue; // Se una fonte è giù, passiamo alla prossima
    }

    $xml = @simplexml_load_string($xmlData);
    if (!$xml) continue;

    // Conversione sicura in Array
    $json = json_encode($xml);
    $data = json_decode($json, true);
    $items = $data['channel']['item'] ?? [];

    // Normalizzazione (gestisce il bug dell'array e del singolo item)
    if (isset($items['title'])) { $items = [$items]; }

    foreach (array_slice($items, 0, 5) as $item) {
        $all_articles[] = [
            "source"      => $source,
            "title"       => is_array($item['title']) ? "Titolo non disponibile" : $item['title'],
            "description" => isset($item['description']) ? (is_array($item['description']) ? "Dettagli nel link." : strip_tags($item['description'])) : "",
            "link"        => $item['link'] ?? "#",
            "date"        => $item['pubDate'] ?? "N/D"
        ];
    }
}

// Se non abbiamo trovato nulla (tutti i feed offline)
if (empty($all_articles)) {
    http_response_code(503);
    echo json_encode(["error" => "Servizio momentaneamente non disponibile"]);
} else {
    echo json_encode($all_articles);
}