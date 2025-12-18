<?php
session_start();
require __DIR__ . "/../../src/i18n.php";

$pseudo = isset($_SESSION["pseudo"]) ? (string)$_SESSION["pseudo"] : null;
$isLoggedIn = $pseudo !== null && $pseudo !== "";
?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities(t("game_2048_title")) ?></title>
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
                <div class="badge"><?= htmlentities(t("game_2048_badge")) ?></div>
                <h1 style="margin:0;"><?= htmlentities(t("game_2048_h1")) ?></h1>
                <p class="helper"><?= htmlentities(t("game_2048_rules")) ?></p>
            </div>
            <div class="right">
                <?= render_lang_switcher() ?>
                <?php if ($isLoggedIn): ?>
                    <div class="pill"><?= htmlentities(t("connected_as", ["pseudo" => $pseudo])) ?></div>
                    <a class="btn btn-secondary" href="/leaderboard.php"><?= htmlentities(t("nav_leaderboard")) ?></a>
                    <a class="btn btn-secondary" href="/profile.php"><?= htmlentities(t("nav_profile")) ?></a>
                    <a class="btn btn-secondary" href="/rooms.php"><?= htmlentities(t("rooms_title")) ?></a>
                    <a class="btn btn-secondary" href="/logout.php"><?= htmlentities(t("nav_logout")) ?></a>
                <?php else: ?>
                    <a class="btn btn-secondary" href="/leaderboard.php"><?= htmlentities(t("nav_leaderboard")) ?></a>
                    <a class="btn btn-secondary" href="/index.html?lang=<?= urlencode(minichat_lang()) ?>">Home</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isLoggedIn): ?>
            <div class="panel" style="margin-bottom:14px;">
                <div class="label-row">
                    <span><?= htmlentities(t("game_2048_need_login")) ?></span>
                    <span class="tag"><?= htmlentities(t("game_2048_guest_note")) ?></span>
                </div>
                <form class="form" method="post" action="/login.php?lang=<?= urlencode(minichat_lang()) ?>&next=<?= urlencode("/games/2048/index.php") ?>">
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
                        <a class="btn btn-secondary" href="/inscription.html?lang=<?= urlencode(minichat_lang()) ?>"><?= htmlentities(t("signup_btn")) ?></a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="layout-split">
            <div class="panel">
                <div class="game-header">
                    <div class="scorebox">
                        <div class="scorebox__label"><?= htmlentities(t("game_2048_score")) ?></div>
                        <div id="score" class="scorebox__value">0</div>
                    </div>
                    <div class="scorebox">
                        <div class="scorebox__label"><?= htmlentities(t("game_2048_best")) ?></div>
                        <div id="best" class="scorebox__value">0</div>
                    </div>
                    <div class="game-actions">
                        <button id="newGame" class="btn"><?= htmlentities(t("game_2048_new")) ?></button>
                        <button id="continueGame" class="btn btn-secondary"><?= htmlentities(t("game_2048_continue")) ?></button>
                    </div>
                    <div id="saveStatus" class="pill muted"></div>
                </div>

                <div class="board" aria-label="2048 board">
                    <?php for ($i = 0; $i < 16; $i++): ?>
                        <div class="cell" aria-hidden="true"></div>
                    <?php endfor; ?>
                    <div id="tileLayer" class="tile-layer" aria-hidden="true"></div>
                </div>

                <div id="overlay" class="overlay" hidden>
                    <div class="overlay__card">
                        <div id="overlayTitle" class="overlay__title">Game Over</div>
                        <div class="actions">
                            <button id="overlayNew" class="btn"><?= htmlentities(t("game_2048_new")) ?></button>
                            <a class="btn btn-secondary" href="/rooms.php"><?= htmlentities(t("rooms_title")) ?></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="label-row">
                    <span><?= htmlentities(t("game_2048_leaderboard")) ?></span>
                    <span class="tag">Top 20</span>
                </div>
                <div id="leaderboard" class="leaderboard"></div>
            </div>
        </div>
    </div>
</div>

<script>
window.__MINICHAT_2048__ = <?= json_encode([
    "lang" => minichat_lang(),
    "isLoggedIn" => $isLoggedIn,
    "labels" => [
        "saving" => t("game_2048_saving"),
        "saved" => t("game_2048_saved"),
        "saveError" => t("game_2048_save_error"),
        "loginToSave" => t("game_2048_login_to_save"),
    ],
], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="game.js"></script>
</body>
</html>
