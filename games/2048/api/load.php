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

$stmt = $pdo->prepare("
    SELECT board, score, best_score
    FROM game_2048_saves
    WHERE pseudo = ?
    LIMIT 1
");
$stmt->execute([$pseudo]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(null);
    exit;
}

$board = json_decode($row["board"], true);
if (!is_array($board)) {
    $board = [];
}

$isOver = false;
if (count($board) === 16) {
    $empty = false;
    for ($i = 0; $i < 16; $i++) {
        if ((int)$board[$i] === 0) {
            $empty = true;
            break;
        }
    }
    if (!$empty) {
        $isOver = true;
        for ($r = 0; $r < 4; $r++) {
            for ($c = 0; $c < 4; $c++) {
                $idx = $r * 4 + $c;
                $v = (int)$board[$idx];
                if ($c < 3 && $v === (int)$board[$idx + 1]) $isOver = false;
                if ($r < 3 && $v === (int)$board[$idx + 4]) $isOver = false;
            }
        }
    }
}

echo json_encode([
    "board" => array_map("intval", $board),
    "score" => (int)$row["score"],
    "best_score" => (int)$row["best_score"],
    "is_over" => $isOver,
], JSON_UNESCAPED_UNICODE);

