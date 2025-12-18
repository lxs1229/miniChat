<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require __DIR__ . "/../../../src/db.php";

$difficulty = strtolower(trim((string)($_GET["difficulty"] ?? "beginner")));
$allowed = ["beginner", "intermediate", "expert"];
if (!in_array($difficulty, $allowed, true)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_difficulty"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT pseudo, best_time_ms
    FROM game_minesweeper_saves
    WHERE difficulty = ? AND best_time_ms IS NOT NULL
    ORDER BY best_time_ms ASC, updated_at DESC
    LIMIT 20
");
$stmt->execute([$difficulty]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array_map(function ($r) {
    return [
        "pseudo" => (string)($r["pseudo"] ?? ""),
        "best_time_ms" => (int)($r["best_time_ms"] ?? 0),
    ];
}, $rows), JSON_UNESCAPED_UNICODE);

