<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

require __DIR__ . "/../../../src/db.php";

$rows = $pdo->query("
    SELECT pseudo, best_score
    FROM game_2048_saves
    ORDER BY best_score DESC, updated_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array_map(function ($r) {
    return [
        "pseudo" => (string)($r["pseudo"] ?? ""),
        "best_score" => (int)($r["best_score"] ?? 0),
    ];
}, $rows), JSON_UNESCAPED_UNICODE);

