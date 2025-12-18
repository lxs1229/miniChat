<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["pseudo"])) {
    http_response_code(401);
    echo json_encode(["error" => "unauthorized"]);
    exit;
}

require __DIR__ . "/../../../src/db.php";

$difficulty = strtolower(trim((string)($_GET["difficulty"] ?? "beginner")));
$allowed = ["beginner", "intermediate", "expert"];
if (!in_array($difficulty, $allowed, true)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_difficulty"]);
    exit;
}

$pseudo = (string)$_SESSION["pseudo"];
$stmt = $pdo->prepare("
    SELECT state, best_time_ms
    FROM game_minesweeper_saves
    WHERE pseudo = ? AND difficulty = ?
    LIMIT 1
");
$stmt->execute([$pseudo, $difficulty]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(null);
    exit;
}

$state = json_decode($row["state"], true);
if (!is_array($state)) $state = null;

echo json_encode([
    "state" => $state,
    "best_time_ms" => $row["best_time_ms"] !== null ? (int)$row["best_time_ms"] : null,
], JSON_UNESCAPED_UNICODE);

