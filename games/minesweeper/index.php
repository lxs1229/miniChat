<?php
session_start();
require __DIR__ . "/../../src/i18n.php";

$pseudo = isset($_SESSION["pseudo"]) ? (string)$_SESSION["pseudo"] : null;
$isLoggedIn = $pseudo !== null && $pseudo !== "";
$lang = minichat_lang();
?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities(t("game_ms_title")) ?></title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="/projects.css">
    <link rel="stylesheet" href="game.css">
</head>
<body>
<div class="grid-overlay" aria-hidden="true"></div>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge"><?= htmlentities(t("game_ms_badge")) ?></div>
                <h1 style="margin:0;"><?= htmlentities(t("game_ms_h1")) ?></h1>
                <p class="helper"><?= htmlentities(t("game_ms_rules")) ?></p>
            </div>
            <div class="right">
                <?php if ($isLoggedIn): ?>
                    <div class="pill"><?= htmlentities(t("connected_as", ["pseudo" => $pseudo])) ?></div>
                <?php endif; ?>
                <?= render_lang_switcher() ?>
                <a class="btn btn-secondary" href="/leaderboard.php"><?= htmlentities(t("nav_leaderboard")) ?></a>
                <?php if ($isLoggedIn): ?>
                    <a class="btn btn-secondary" href="/profile.php"><?= htmlentities(t("nav_profile")) ?></a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="/rooms.php"><?= htmlentities(t("rooms_title")) ?></a>
                <?php if ($isLoggedIn): ?>
                    <a class="btn btn-secondary" href="/logout.php"><?= htmlentities(t("nav_logout")) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isLoggedIn): ?>
            <div class="panel" style="margin-bottom:14px;">
                <div class="label-row">
                    <span><?= htmlentities(t("game_2048_need_login")) ?></span>
                    <span class="tag"><?= htmlentities(t("game_ms_guest_note")) ?></span>
                </div>
                <form class="form" method="post" action="/login.php?lang=<?= urlencode($lang) ?>&next=<?= urlencode("/games/minesweeper/index.php") ?>">
                    <div class="layout-split">
                        <div class="field">
                            <div class="label-row">
                                <label for="pseudo"><?= htmlentities(t("login_username")) ?></label>
                                <span class="muted"><?= htmlentities(t("login_required")) ?></span>
                            </div>
                            <input id="pseudo" type="text" name="pseudo" required>
                        </div>
                        <div class="field">
                            <div class="label-row">
                                <label for="mdp"><?= htmlentities(t("login_password")) ?></label>
                                <span class="muted"><?= htmlentities(t("login_required")) ?></span>
                            </div>
                            <input id="mdp" type="password" name="mdp" required>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit"><?= htmlentities(t("login_btn")) ?></button>
                        <a class="btn btn-secondary" href="/inscription.html?lang=<?= urlencode($lang) ?>"><?= htmlentities(t("signup_btn")) ?></a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="ms-layout">
            <div class="panel">
                <div class="ms-controls">
                    <div class="ms-toolbar">
                        <div class="field ms-difficulty">
                            <label for="difficulty"><?= htmlentities(t("game_ms_difficulty")) ?></label>
                            <select id="difficulty">
                                <option value="beginner"><?= htmlentities(t("game_ms_beginner")) ?></option>
                                <option value="intermediate"><?= htmlentities(t("game_ms_intermediate")) ?></option>
                                <option value="expert"><?= htmlentities(t("game_ms_expert")) ?></option>
                            </select>
                        </div>

                        <div class="actions ms-actions">
                            <button id="newGame" class="btn"><?= htmlentities(t("game_ms_new")) ?></button>
                            <button id="continueGame" class="btn btn-secondary"><?= htmlentities(t("game_ms_continue")) ?></button>
                            <button id="flagMode" class="btn btn-secondary" type="button"></button>
                        </div>

                        <div id="saveStatus" class="pill muted ms-status"></div>
                    </div>

                    <div class="ms-statbar" aria-label="Stats">
                        <div class="pill ms-pill"><span class="muted"><?= htmlentities(t("game_ms_mines")) ?>:</span> <b id="minesLeft">0</b></div>
                        <div class="pill ms-pill"><span class="muted"><?= htmlentities(t("game_ms_flags")) ?>:</span> <b id="flags">0</b></div>
                        <div class="pill ms-pill"><span class="muted"><?= htmlentities(t("game_ms_time")) ?>:</span> <b id="time">0.0s</b></div>
                    </div>
                </div>

                <div class="ms-board-wrap">
                    <div id="board" class="ms-board" aria-label="Minesweeper board"></div>
                    <div id="overlay" class="overlay" hidden>
                        <div class="overlay__card">
                            <div id="overlayTitle" class="overlay__title">Game Over</div>
                            <div class="actions">
                                <button id="overlayNew" class="btn"><?= htmlentities(t("game_ms_new")) ?></button>
                                <a class="btn btn-secondary" href="/rooms.php"><?= htmlentities(t("rooms_title")) ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="label-row">
                    <span><?= htmlentities(t("game_ms_leaderboard")) ?></span>
                    <span class="tag">Top 20</span>
                </div>
                <div id="leaderboard" class="leaderboard"></div>
            </div>
        </div>
    </div>
</div>

<script>
window.__MINICHAT_MS__ = <?= json_encode([
    "lang" => $lang,
    "isLoggedIn" => $isLoggedIn,
    "labels" => [
        "saving" => t("game_ms_saving"),
        "saved" => t("game_ms_saved"),
        "saveError" => t("game_ms_save_error"),
        "loginToSave" => t("game_ms_login_to_save"),
        "flagModeOn" => t("game_ms_flag_mode_on"),
        "flagModeOff" => t("game_ms_flag_mode_off"),
    ],
], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="game.js"></script>
</body>
</html>
