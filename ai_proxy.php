<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['pseudo'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non authentifié"]);
    exit;
}

// Rate limit simple
$now = time();
$window = 60;
$limit = 10;
$_SESSION['ai_requests'] = array_filter($_SESSION['ai_requests'] ?? [], function($t) use ($now, $window) {
    return ($now - $t) <= $window;
});
if (count($_SESSION['ai_requests']) >= $limit) {
    http_response_code(429);
    echo json_encode(["error" => "Trop de requêtes IA, réessaie dans une minute."]);
    exit;
}
$_SESSION['ai_requests'][] = $now;

// Lire JSON
$input = json_decode(file_get_contents("php://input"), true);
$messages = $input['messages'] ?? null;
$model = $input['model'] ?? "llama3-70b-8192";

if (!is_array($messages) || count($messages) === 0) {
    http_response_code(400);
    echo json_encode(["error" => "Messages manquants"]);
    exit;
}

$messages = array_slice($messages, -20);

$apiKey = getenv("GROQ_API_KEY");
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["error" => "GROQ_API_KEY manquant"]);
    exit;
}

$payload = [
    "model" => $model,
    "messages" => $messages,
    "max_tokens" => 400,
    "temperature" => 0.4
];

// Requête Groq
$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(["error" => "Erreur réseau Groq : {$curlError}"]);
    exit;
}

$data = json_decode($response, true);

if (!$data || $statusCode >= 400) {
    http_response_code(502);
    echo json_encode(["error" => $data['error']['message'] ?? "Réponse IA invalide"]);
    exit;
}

// Réponse finale
$reply = $data['choices'][0]['message']['content'] ?? "(réponse vide)";
echo json_encode(["reply" => $reply]);
