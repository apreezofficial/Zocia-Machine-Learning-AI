<?php
header("Content-Type: application/json");

// === CONFIG ===
$googleApiKey = "env.chat.preciousadedokun.com.ng/gapi";
$searchEngineId = "env.chat.preciousadedokun.com.ng/csx";

// === UTILITIES ===

function getPersonalComment($name) {
    $lines = [
        "Hey $name, here’s a smart breakdown just for you!",
        "$name, I scanned the web and here’s what caught my AI eyes.",
        "Alright $name, let’s decode this in my own words!",
        "$name, after a deep dive into the web’s knowledge pool, here’s what I found:"
    ];
    return $lines[array_rand($lines)];
}

function googleSearch($query, $apiKey, $cx) {
    $queryEncoded = urlencode($query);
    $url = "https://www.googleapis.com/customsearch/v1?q=$queryEncoded&key=$apiKey&cx=$cx";

    $json = file_get_contents($url);
    $data = json_decode($json, true);

    return $data['items'][0]['link'] ?? null;
}

function scrapeText($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return false;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    return trim(preg_replace('/\s+/', ' ', $dom->textContent));
}

function generateAIStyleResponse($query, $text, $user) {
    $summary = summarize($text);
    $personal = getPersonalComment($user);

    return "$personal Based on your query \"$query\", I explored the web and interpreted this: \"$summary\" — isn’t that quite interesting? Let me know if you want a deeper dive!";
}

function summarize($text) {
    $text = substr($text, 0, 500);
    $sentences = explode('.', $text);
    return ucfirst(trim($sentences[0])) . '.';
}

// === MAIN ===

if (!isset($_GET['question'])) {
    echo json_encode(["error" => "Missing ?question=... parameter"]);
    exit;
}

$question = $_GET['question'];
$user = $_GET['user'] ?? "Explorer";

// Step 1: Search Google
$url = googleSearch($question, $googleApiKey, $searchEngineId);

if (!$url) {
    echo json_encode(["error" => "No relevant result found. Try a more specific question."]);
    exit;
}

// Step 2: Scrape content
$content = scrapeText($url);

if (!$content) {
    echo json_encode(["error" => "Couldn't scrape the site content from: $url"]);
    exit;
}

// Step 3: Respond like an AI
$response = generateAIStyleResponse($question, $content, $user);

// Step 4: Output
echo json_encode([
    "user" => $user,
    "question" => $question,
    "source_url" => $url,
    "ai_response" => $response,
    "summary" => summarize($content)
], JSON_PRETTY_PRINT);

?>
