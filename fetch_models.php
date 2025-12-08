<?php
// 返回 Groq 最新模型列表（动态）
header("Content-Type: application/json");

$apiKey = getenv("GROQ_API_KEY");
if (!$apiKey) {
    echo json_encode(["error" => "Missing GROQ_API_KEY"]);
    exit;
}

$ch = curl_init("https://api.groq.com/openai/v1/models");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(["error" => "Erreur API : $error"]);
    exit;
}

$data = json_decode($response, true);

// 筛选 is_deprecated = false 的模型
$models = array_filter($data["data"] ?? [], function($m) {
    return empty($m["deprecated"]);
});

// 只返回模型 ID 列表
$result = array_map(function($m) {
    return $m["id"];
}, $models);

echo json_encode($result);
