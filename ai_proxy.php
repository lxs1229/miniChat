<?php
session_start();
header("Content-Type: application/json");

// DEBUG : Log input
$raw = file_get_contents("php://input");
error_log("AI_PROXY INPUT = " . $raw);

if (!isset($_SESSION['pseudo'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non authentifié"]);
    exit;
}

$input = json_decode($raw, true);

// DEBUG
error_log("AI_PROXY DECODED = " . print_r($input, true));

$messages = $input['messages'] ?? null;

// DEBUG
if ($messages === null) {
    error_log("AI_PROXY: messages est NULL !");
} elseif ($messages === []) {
    error_log("AI_PROXY: messages est un tableau VIDE !");
}

// 👍 更清晰的错误信息
if (!is_array($messages) || count($messages) === 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Messages manquants",
        "debug_raw" => $raw,
        "debug_decoded" => $input
    ]);
    exit;
}

$model = $input['model'] ?? 'llama3-70b-8192';

$apiKey = getenv("GROQ_API_KEY");
if (!$apiKey) {
    echo json_encode(["error" => "Missing GROQ_API_KEY"]);
    exit;
}

$payload = [
    "model" => $model,
    "messages" => $messages,
    "max_tokens" => 512,
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
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
echo json_encode([
    "debug_sent" => $payload,
    "debug_response" => $data,
    "status" => $statusCode
]);
