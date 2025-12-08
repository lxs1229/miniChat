<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['pseudo'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non authentifié"]);
    exit;
}

// Limite simple : max 10 requêtes IA par minute et par session
$now = time();
$window = 60;
$limit = 10;
$_SESSION['ai_requests'] = array_filter($_SESSION['ai_requests'] ?? [], function($ts) use ($now, $window) {
    return ($now - $ts) <= $window;
});
if (count($_SESSION['ai_requests']) >= $limit) {
    http_response_code(429);
    echo json_encode(["error" => "Trop de requêtes IA, réessaie dans une minute."]);
    exit;
}
$_SESSION['ai_requests'][] = $now;

$input = json_decode(file_get_contents("php://input"), true);
$messages = $input['messages'] ?? [];
$model = $input['model'] ?? 'llama3-70b-8192';

if (!is_array($messages) || !$messages) {
    http_response_code(400);
    echo json_encode(["error" => "Messages manquants"]);
    exit;
}

// Conserver seulement les 20 derniers échanges pour limiter la charge
$messages = array_slice($messages, -20);

$secretsPath = __DIR__ . '/../secret.php';
if (file_exists($secretsPath)) {
    require_once $secretsPath;
}

$apiKey = getenv("GROQ_API_KEY") ?: (defined('GROQ_API_KEY') ? GROQ_API_KEY : null);
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["error" => "Configure la variable d'environnement GROQ_API_KEY ou ajoute un fichier secret.php contenant la constante GROQ_API_KEY."]);
    exit;
}

$payload = [
    "model" => $model,
    "messages" => $messages,
    "max_tokens" => 512,
    "temperature" => 0.4,
];

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json",
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(["error" => "Appel Groq échoué : {$curlError}"]);
    exit;
}

$data = json_decode($response, true);
if ($statusCode >= 400 || !$data || empty($data['choices'][0]['message']['content'])) {
    http_response_code(502);
    $message = $data['error']['message'] ?? 'Réponse IA invalide';
    echo json_encode(["error" => $message]);
    exit;
}

$reply = $data['choices'][0]['message']['content'];
echo json_encode(["reply" => $reply]);
