<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["pseudo"])) {
    http_response_code(401);
    echo json_encode(["error" => "unauthorized"]);
    exit;
}

require __DIR__ . "/../../../src/db.php";

$pseudo = (string)$_SESSION["pseudo"];
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_json"]);
    exit;
}

$board = $input["board"] ?? null;
$score = $input["score"] ?? null;
$best = $input["best_score"] ?? null;
$over = (bool)($input["over"] ?? false);

if (!is_array($board) || count($board) !== 16) {
    http_response_code(400);
    echo json_encode(["error" => "invalid_board"]);
    exit;
}

$board = array_map(function ($v) {
    $n = (int)$v;
    return $n < 0 ? 0 : $n;
}, $board);

$score = max(0, (int)$score);
$best = max(0, (int)$best);
if ($best < $score) $best = $score;

$stmt = $pdo->prepare("
    INSERT INTO game_2048_saves (pseudo, board, score, best_score, updated_at)
    VALUES (?, ?::jsonb, ?, ?, NOW())
    ON CONFLICT (pseudo)
    DO UPDATE SET
        board = EXCLUDED.board,
        score = EXCLUDED.score,
        best_score = GREATEST(game_2048_saves.best_score, EXCLUDED.best_score),
        updated_at = NOW()
");
$stmt->execute([$pseudo, json_encode($board), $score, $best]);

if ($over) {
    $pdo->prepare("INSERT INTO game_2048_scores (pseudo, score) VALUES (?, ?)")
        ->execute([$pseudo, $score]);
}

echo json_encode(["ok" => true], JSON_UNESCAPED_UNICODE);

