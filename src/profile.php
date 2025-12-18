<?php
session_start();
require __DIR__ . "/i18n.php";

if (!isset($_SESSION["pseudo"]) || $_SESSION["pseudo"] === "") {
    header("Location: /index.html?lang=" . urlencode(minichat_lang()) . "&next=" . urlencode("/profile.php"));
    exit;
}

$pseudo = (string)$_SESSION["pseudo"];

require __DIR__ . "/db.php";

function table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT to_regclass(?)");
    $stmt->execute(["public." . $table]);
    return $stmt->fetchColumn() !== null;
}

$schema = [
    "users" => table_exists($pdo, "users"),
    "rooms" => table_exists($pdo, "rooms"),
    "messages" => table_exists($pdo, "messages"),
    "connect_history" => table_exists($pdo, "connect_history"),
    "game_2048_saves" => table_exists($pdo, "game_2048_saves"),
    "game_minesweeper_saves" => table_exists($pdo, "game_minesweeper_saves"),
];

$stats = [
    "messages" => null,
    "roomsCreated" => null,
    "lastLoginAt" => null,
    "lastLoginIp" => null,
    "best2048" => null,
    "msBest" => [
        "beginner" => null,
        "intermediate" => null,
        "expert" => null,
    ],
];

if ($schema["messages"]) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    $stats["messages"] = (int)$stmt->fetchColumn();
}

if ($schema["rooms"]) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE created_by = ?");
    $stmt->execute([$pseudo]);
    $stats["roomsCreated"] = (int)$stmt->fetchColumn();
}

if ($schema["connect_history"]) {
    $stmt = $pdo->prepare("
        SELECT time, ip_connection
        FROM connect_history
        WHERE pseudo = ?
        ORDER BY time DESC
        LIMIT 1
    ");
    $stmt->execute([$pseudo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($row) {
        $stats["lastLoginAt"] = $row["time"] ?? null;
        $stats["lastLoginIp"] = $row["ip_connection"] ?? null;
    }
}

if ($schema["game_2048_saves"]) {
    $stmt = $pdo->prepare("SELECT best_score FROM game_2048_saves WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    $val = $stmt->fetchColumn();
    if ($val !== false && $val !== null) $stats["best2048"] = (int)$val;
}

if ($schema["game_minesweeper_saves"]) {
    $stmt = $pdo->prepare("
        SELECT difficulty, best_time_ms
        FROM game_minesweeper_saves
        WHERE pseudo = ? AND best_time_ms IS NOT NULL
    ");
    $stmt->execute([$pseudo]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $diff = (string)($row["difficulty"] ?? "");
        $ms = $row["best_time_ms"];
        if (!array_key_exists($diff, $stats["msBest"])) continue;
        $stats["msBest"][$diff] = $ms === null ? null : (int)$ms;
    }
}

function fmt_ms_time(?int $ms): string {
    if ($ms === null) return "—";
    $sec = $ms / 1000;
    return number_format($sec, 2) . "s";
}
?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities(t("profile_title")) ?> • MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="grid-overlay" aria-hidden="true"></div>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge"><?= htmlentities(t("profile_badge")) ?></div>
                <h1><?= htmlentities(t("profile_h1")) ?></h1>
                <p class="helper"><?= htmlentities(t("profile_helper")) ?></p>
            </div>
            <div class="right">
                <div class="pill"><?= htmlentities(t("connected_as", ["pseudo" => $pseudo])) ?></div>
                <?= render_lang_switcher() ?>
                <a class="btn btn-secondary" href="rooms.php"><?= htmlentities(t("nav_rooms")) ?></a>
                <a class="btn btn-secondary" href="ai.php"><?= htmlentities(t("nav_ai")) ?></a>
                <a class="btn btn-secondary" href="leaderboard.php"><?= htmlentities(t("nav_leaderboard")) ?></a>
                <a class="btn btn-secondary" href="logout.php"><?= htmlentities(t("nav_logout")) ?></a>
            </div>
        </div>

        <div class="layout-split">
            <div class="panel">
                <div class="label-row">
                    <span><?= htmlentities(t("profile_overview")) ?></span>
                    <span class="tag"><?= htmlentities($pseudo) ?></span>
                </div>
                <div class="leaderboard" style="margin-top:12px;">
                    <div class="lb-row">
                        <div class="lb-name"><?= htmlentities(t("profile_messages")) ?></div>
                        <div class="lb-score"><?= $stats["messages"] === null ? "—" : (int)$stats["messages"] ?></div>
                    </div>
                    <div class="lb-row">
                        <div class="lb-name"><?= htmlentities(t("profile_rooms_created")) ?></div>
                        <div class="lb-score"><?= $stats["roomsCreated"] === null ? "—" : (int)$stats["roomsCreated"] ?></div>
                    </div>
                    <div class="lb-row">
                        <div class="lb-name"><?= htmlentities(t("profile_last_login")) ?></div>
                        <div class="lb-score"><?= htmlentities((string)($stats["lastLoginAt"] ?? "—")) ?></div>
                    </div>
                    <div class="lb-row">
                        <div class="lb-name"><?= htmlentities(t("profile_last_ip")) ?></div>
                        <div class="lb-score"><?= htmlentities((string)($stats["lastLoginIp"] ?? "—")) ?></div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="label-row">
                    <span><?= htmlentities(t("profile_best")) ?></span>
                    <span class="tag"><?= htmlentities(t("profile_best_tag")) ?></span>
                </div>
                <div class="leaderboard" style="margin-top:12px;">
                    <div class="lb-row">
                        <div class="lb-name">2048</div>
                        <div class="lb-score"><?= $stats["best2048"] === null ? "—" : (int)$stats["best2048"] ?></div>
                    </div>
                    <div class="lb-row">
                        <div class="lb-name"><?= htmlentities(t("profile_ms_beginner")) ?></div>
                        <div class="lb-score"><?= htmlentities(fmt_ms_time($stats["msBest"]["beginner"])) ?></div>
                    </div>
                    <div class="lb-row">
                        <div class="lb-name"><?= htmlentities(t("profile_ms_intermediate")) ?></div>
                        <div class="lb-score"><?= htmlentities(fmt_ms_time($stats["msBest"]["intermediate"])) ?></div>
                    </div>
                    <div class="lb-row">
                        <div class="lb-name"><?= htmlentities(t("profile_ms_expert")) ?></div>
                        <div class="lb-score"><?= htmlentities(fmt_ms_time($stats["msBest"]["expert"])) ?></div>
                    </div>
                </div>

                <div class="actions" style="margin-top:14px;">
                    <a class="btn btn-secondary" href="/games/2048/index.php"><?= htmlentities(t("profile_play_2048")) ?></a>
                    <a class="btn btn-secondary" href="/games/minesweeper/index.php"><?= htmlentities(t("profile_play_ms")) ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
