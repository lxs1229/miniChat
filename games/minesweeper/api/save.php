<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["pseudo"])) {
    http_response_code(401);
    echo json_encode(["error" => "unauthorized"]);
    exit;
}

require __DIR__ . "/../../../src/db.php";

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_json"]);
    exit;
}

$difficulty = strtolower(trim((string)($input["difficulty"] ?? "beginner")));
$allowed = ["beginner", "intermediate", "expert"];
if (!in_array($difficulty, $allowed, true)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_difficulty"]);
    exit;
}

$state = $input["state"] ?? null;
if (!is_array($state)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_state"]);
    exit;
}

$timeMs = isset($input["time_ms"]) ? (int)$input["time_ms"] : null;
$won = (bool)($input["won"] ?? false);

$pseudo = (string)$_SESSION["pseudo"];

// best time update only on win
$bestTimeMs = null;
if ($won && $timeMs !== null && $timeMs > 0) {
    $bestTimeMs = $timeMs;
}

$stmt = $pdo->prepare("
    INSERT INTO game_minesweeper_saves (pseudo, difficulty, state, best_time_ms, updated_at)
    VALUES (?, ?, ?::jsonb, ?, NOW())
    ON CONFLICT (pseudo, difficulty)
    DO UPDATE SET
        state = EXCLUDED.state,
        best_time_ms = CASE
            WHEN EXCLUDED.best_time_ms IS NULL THEN game_minesweeper_saves.best_time_ms
            WHEN game_minesweeper_saves.best_time_ms IS NULL THEN EXCLUDED.best_time_ms
            ELSE LEAST(game_minesweeper_saves.best_time_ms, EXCLUDED.best_time_ms)
        END,
        updated_at = NOW()
");
$stmt->execute([$pseudo, $difficulty, json_encode($state), $bestTimeMs]);

if ($won && $timeMs !== null && $timeMs > 0) {
    $pdo->prepare("INSERT INTO game_minesweeper_scores (pseudo, difficulty, time_ms) VALUES (?, ?, ?)")
        ->execute([$pseudo, $difficulty, $timeMs]);
}

echo json_encode(["ok" => true], JSON_UNESCAPED_UNICODE);

