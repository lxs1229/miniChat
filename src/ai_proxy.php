<?php
session_start();
header("Content-Type: application/json");

// Vérification login
if (!isset($_SESSION['pseudo'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non authentifié"]);
    exit;
}

// Anti-spam IA (10 req / minute)
$now = time();
$window = 60;
$limit = 10;

$_SESSION['ai_requests'] = array_filter($_SESSION['ai_requests'] ?? [], function ($t) use ($now, $window) {
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
$messages = $input["messages"] ?? null;
$requestedModel = $input["model"] ?? null;

// Charger modèle recommandé par défaut
$defaultModel = "llama-3.3-70b-versatile";

// Charger liste des modèles depuis fetch_models.php (sécurisé)
$availableModels = [];
$modelFile = __DIR__ . "/../models_cache.json";

// Rafraîchir modèle toutes les 2 minutes (évite spam API Groq)
if (file_exists($modelFile) && (time() - filemtime($modelFile) < 120)) {
    $availableModels = json_decode(file_get_contents($modelFile), true);
} else {
    $curl = curl_init("https://api.groq.com/openai/v1/models");
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer " . getenv("GROQ_API_KEY")]
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    $raw = json_decode($response, true);
    $availableModels = array_map(fn($m) => $m["id"], $raw["data"] ?? []);
    file_put_contents($modelFile, json_encode($availableModels));
}

// Vérifier modèle envoyé par le client
if (!$requestedModel || !in_array($requestedModel, $availableModels)) {
    $model = $defaultModel;  // fallback automatique
} else {
    $model = $requestedModel;
}

// Vérification messages
if (!is_array($messages) || count($messages) === 0) {
    http_response_code(400);
    echo json_encode(["error" => "Messages manquants"]);
    exit;
}

// Éviter surcharge
$messages = array_slice($messages, -20);

// Récupérer clé API
$apiKey = getenv("GROQ_API_KEY");
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["error" => "GROQ_API_KEY manquant"]);
    exit;
}

// Préparer requête Groq
$payload = [
    "model" => $model,
    "messages" => $messages,
    "max_tokens" => 400,
    "temperature" => 0.4
];

// Envoyer à Groq
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
    echo json_encode(["error" => $data["error"]["message"] ?? "Réponse IA invalide"]);
    exit;
}

// Réponse finale
$reply = $data["choices"][0]["message"]["content"] ?? "(réponse vide)";
echo json_encode([
    "reply" => $reply,
    "model_used" => $model
]);
?>
