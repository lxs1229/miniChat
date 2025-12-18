<?php
session_start();
require __DIR__ . "/i18n.php";

$pseudo = isset($_SESSION["pseudo"]) ? (string)$_SESSION["pseudo"] : null;
$isLoggedIn = $pseudo !== null && $pseudo !== "";
?>
<!DOCTYPE html>
<html lang="<?= htmlentities(minichat_html_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlentities(t("leaderboard_title")) ?> • MiniChat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="grid-overlay" aria-hidden="true"></div>
<div class="page">
    <div class="card">
        <div class="topbar">
            <div class="stacked">
                <div class="badge"><?= htmlentities(t("leaderboard_badge")) ?></div>
                <h1><?= htmlentities(t("leaderboard_h1")) ?></h1>
                <p class="helper"><?= htmlentities(t("leaderboard_helper")) ?></p>
            </div>
            <div class="right">
                <?php if ($isLoggedIn): ?>
                    <div class="pill"><?= htmlentities(t("connected_as", ["pseudo" => $pseudo])) ?></div>
                <?php endif; ?>
                <?= render_lang_switcher() ?>
                <a class="btn btn-secondary" href="/games/2048/index.php"><?= htmlentities(t("leaderboard_nav_2048")) ?></a>
                <a class="btn btn-secondary" href="/games/minesweeper/index.php"><?= htmlentities(t("leaderboard_nav_ms")) ?></a>
                <a class="btn btn-secondary" href="rooms.php"><?= htmlentities(t("nav_rooms")) ?></a>
                <a class="btn btn-secondary" href="ai.php"><?= htmlentities(t("nav_ai")) ?></a>
                <?php if ($isLoggedIn): ?>
                    <a class="btn btn-secondary" href="profile.php"><?= htmlentities(t("nav_profile")) ?></a>
                    <a class="btn btn-secondary" href="logout.php"><?= htmlentities(t("nav_logout")) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="layout-split">
            <div class="panel">
                <div class="label-row">
                    <span>2048</span>
                    <span class="tag">Top 20</span>
                </div>
                <div id="lb2048" class="leaderboard" style="margin-top:12px;"></div>
            </div>

            <div class="panel">
                <div class="label-row">
                    <span><?= htmlentities(t("leaderboard_ms_title")) ?></span>
                    <span class="tag">Top 20</span>
                </div>
                <div class="actions" style="margin:12px 0 10px;">
                    <button class="btn btn-secondary is-active" type="button" data-diff="beginner"><?= htmlentities(t("leaderboard_ms_beginner")) ?></button>
                    <button class="btn btn-secondary" type="button" data-diff="intermediate"><?= htmlentities(t("leaderboard_ms_intermediate")) ?></button>
                    <button class="btn btn-secondary" type="button" data-diff="expert"><?= htmlentities(t("leaderboard_ms_expert")) ?></button>
                </div>
                <div id="lbMs" class="leaderboard"></div>
            </div>
        </div>
    </div>
</div>

<script>
const LB_I18N = <?= json_encode([
    "rank" => t("leaderboard_rank"),
    "empty" => t("leaderboard_empty"),
], JSON_UNESCAPED_UNICODE) ?>;

function rowHtml(rank, name, score) {
  return `
    <div class="lb-row">
      <div class="lb-rank">#${rank}</div>
      <div class="lb-name">${name}</div>
      <div class="lb-score">${score}</div>
    </div>
  `;
}

async function load2048() {
  const el = document.getElementById("lb2048");
  if (!el) return;
  el.innerHTML = "";
  try {
    const res = await fetch("/games/2048/api/leaderboard.php", { cache: "no-store" });
    if (!res.ok) throw new Error("bad_status");
    const rows = await res.json();
    if (!Array.isArray(rows) || rows.length === 0) {
      el.innerHTML = `<p class="muted">${LB_I18N.empty}</p>`;
      return;
    }
    el.innerHTML = rows.map((r, i) => rowHtml(i + 1, (r.pseudo || "-"), String(r.best_score ?? 0))).join("");
  } catch {
    el.innerHTML = `<p class="muted">${LB_I18N.empty}</p>`;
  }
}

function fmtMs(ms) {
  const n = Number(ms) || 0;
  return `${(n / 1000).toFixed(2)}s`;
}

async function loadMs(diff) {
  const el = document.getElementById("lbMs");
  if (!el) return;
  el.innerHTML = "";
  try {
    const res = await fetch(`/games/minesweeper/api/leaderboard.php?difficulty=${encodeURIComponent(diff)}`, { cache: "no-store" });
    if (!res.ok) throw new Error("bad_status");
    const rows = await res.json();
    if (!Array.isArray(rows) || rows.length === 0) {
      el.innerHTML = `<p class="muted">${LB_I18N.empty}</p>`;
      return;
    }
    el.innerHTML = rows.map((r, i) => rowHtml(i + 1, (r.pseudo || "-"), fmtMs(r.best_time_ms))).join("");
  } catch {
    el.innerHTML = `<p class="muted">${LB_I18N.empty}</p>`;
  }
}

function setDiffActive(diff) {
  document.querySelectorAll("button[data-diff]").forEach((btn) => {
    btn.classList.toggle("is-active", btn.getAttribute("data-diff") === diff);
  });
}

document.querySelectorAll("button[data-diff]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const diff = btn.getAttribute("data-diff");
    if (!diff) return;
    setDiffActive(diff);
    loadMs(diff);
  });
});

load2048();
loadMs("beginner");
</script>
</body>
</html>

